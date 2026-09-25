<?php
/**
 * PoloNav registration.
 *
 * Passenger:
 * - Creates a users row with role = passenger
 * - Creates a matching passengers row
 *
 * Driver:
 * - Creates a users row with role = driver
 * - Creates a matching drivers row
 * - Requires a valid ID image
 * - Drivers additionally require license, OR/CR, CTPL, MVIR, and emission documents
 */

require_once __DIR__ . '/includes/bootstrap.php';

if (request_method() !== 'POST') {
    json_error('Use POST to register.', 405);
}

$input = request_input();

require_fields($input, ['username', 'password', 'name']);

$username = trim_string($input['username']);
$password = (string) $input['password'];
$name = trim_string($input['name']);
$contact = trim_string($input['contact_number'] ?? '');
$address = trim_string($input['address'] ?? '');
$role = trim_string($input['role'] ?? 'passenger');

if (strlen($username) < 3) {
    json_error('Username must be at least 3 characters.', 422);
}

if (strlen($password) < 8) {
    json_error('Password must be at least 8 characters.', 422);
}

if (!in_array($role, ['passenger', 'driver'], true)) {
    json_error('Invalid account type.', 422);
}

$passwordHash = hash_password($password);

$pdo = get_pdo();

try {
    $documentFiles = [];
    $uploadFolder = __DIR__ . '/uploads/driver_documents/';
    if (!is_dir($uploadFolder) && !mkdir($uploadFolder, 0755, true)) {
        json_error('Unable to prepare document storage.', 500);
    }

    $documentFields = $role === 'driver'
        ? ['license', 'valid_id', 'or_cr', 'ctpl', 'mvir', 'emission']
        : ['passenger_valid_id'];

    foreach ($documentFields as $field) {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            json_error('All required verification documents must be uploaded.', 422);
        }
        $file = $_FILES[$field];
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
            json_error('Each verification document must be a valid file of 5 MB or smaller.', 422);
        }
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            json_error('Verification documents must be JPG or PNG images.', 422);
        }
        $extension = $mime === 'image/png' ? 'png' : 'jpg';
        $fileName = $field . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], $uploadFolder . $fileName)) {
            json_error('Unable to save verification documents.', 500);
        }
        $documentFiles[$field] = $fileName;
    }

    /*
     * DATABASE TRANSACTION
     */
    $pdo->beginTransaction();

    /*
     * Create user account.
     */
    $stmt = $pdo->prepare(
        'INSERT INTO users
            (username, password, role)
         VALUES
            (:username, :password, :role)'
    );

    $stmt->execute([
        'username' => $username,
        'password' => $passwordHash,
        'role'     => $role,
    ]);

    $userId = (int) $pdo->lastInsertId();

    /*
     * PASSENGER
     */
    if ($role === 'passenger') {

        $stmt = $pdo->prepare(
            'INSERT INTO passengers
                (name, contact_number, address, valid_id_file, users_id)
             VALUES
                (:name, :contact_number, :address, :valid_id_file, :users_id)'
        );

        $stmt->execute([
            'name'           => $name,
            'contact_number' => $contact !== '' ? $contact : null,
            'address'        => $address !== '' ? $address : null,
            'valid_id_file'  => $documentFiles['passenger_valid_id'],
            'users_id'       => $userId,
        ]);
    }

    /*
     * DRIVER
     */
    if ($role === 'driver') {

        /*
         * For now, use the username as the initial
         * license number until you add a license-number
         * field to the registration form.
         *
         * You should ideally add a separate
         * "License Number" field later.
         */
        $licenseNumber = $username;

        $stmt = $pdo->prepare(
            'INSERT INTO drivers
                (
                    full_name,
                    contact_number,
                    license_number,
                    status,
                    license_file,
                    valid_id_file,
                    or_cr_file,
                    ctpl_file,
                    mvir_file,
                    emission_file,
                    users_id
                )
             VALUES
                (
                    :full_name,
                    :contact_number,
                    :license_number,
                    :status,
                    :license_file,
                    :valid_id_file,
                    :or_cr_file,
                    :ctpl_file,
                    :mvir_file,
                    :emission_file,
                    :users_id
                )'
        );

        $stmt->execute([
            'full_name'      => $name,
            'contact_number' => $contact !== '' ? $contact : null,
            'license_number' => $licenseNumber,
            'status'         => 'pending',
            'license_file'   => $documentFiles['license'],
            'valid_id_file'  => $documentFiles['valid_id'],
            'or_cr_file'     => $documentFiles['or_cr'],
            'ctpl_file'      => $documentFiles['ctpl'],
            'mvir_file'      => $documentFiles['mvir'],
            'emission_file'  => $documentFiles['emission'],
            'users_id'       => $userId,
        ]);
    }

    $pdo->commit();

    /*
     * Automatically log the user in.
     */
    $user = authenticate($pdo, $username, $password);

    json_success($user, 201);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    /*
     * If the database fails after files were uploaded,
     * remove the uploaded documents.
     */
    foreach ($documentFiles as $documentFile) {
        $file = __DIR__ . '/uploads/driver_documents/' . $documentFile;
        if (file_exists($file)) {
            unlink($file);
        }
    }

    handle_pdo_exception(
        $e,
        'Unable to create the account.'
    );

} catch (RuntimeException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_error($e->getMessage(), 500);
}