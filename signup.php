<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/web.php';

if (current_user()) {
    web_redirect(ltrim(dashboard_path_for_role(current_user()['role']), '/'));
}

public_layout_start('Register');
?>
<div class="auth-page signup-page">
    <a class="auth-home-logo" href="<?= htmlspecialchars(web_url('index.php')) ?>" aria-label="Back to PoloNav landing page">
        <img src="<?= htmlspecialchars(web_url('assets/images/polonav.svg')) ?>" alt="PoloNav">
    </a>
    <div class="auth-showcase">
        <div class="auth-showcase-copy">
            <p class="landing-eyebrow">WELCOME TO POLONAV</p>
            <h1>Move through the Antipolo city <em>with confidence.</em></h1>
            <p>Join a calm, clear workspace for finding routes, estimating fares, and managing every trip.</p>
        </div>
    </div>
    <div class="auth-panel">
        <p class="landing-eyebrow">NEW ACCOUNT</p>
        <h2>Create your PoloNav account</h2>
        <p class="auth-subtitle">Choose the account type that fits your role.</p>
        <p id="auth-error" class="badge badge-off" hidden></p>
        <form id="signup-form" class="stack">
            <fieldset class="role-choice">
                <legend>I want to register as:</legend>
                <label><input type="radio" name="role" value="passenger" checked> <strong>Passenger</strong><small>Find routes, calculate fares, and manage trips.</small></label>
                <label><input type="radio" name="role" value="driver"> <strong>Driver</strong><small>Submit your documents for administrator verification.</small></label>
            </fieldset>
            <div>
                <label for="name">Full name</label>
                <input id="name" name="name" required>
            </div>
            <div>
                <label for="username">Username</label>
                <input id="username" name="username" required>
            </div>
            <div>
                <label for="password">Password (8+ characters)</label>
                <input id="password" name="password" type="password" required minlength="8">
            </div>
            <div>
                <label for="confirm_password">Confirm password</label>
                <input id="confirm_password" name="confirm_password" type="password" required minlength="8">
            </div>
            <div>
                <label for="contact_number">Contact number</label>
                <input id="contact_number" name="contact_number">
            </div>
            <div>
                <label for="address">Address</label>
                <input id="address" name="address">
            </div>
            <div id="passengerAttachment">
                <label for="passenger_valid_id">Valid ID</label>
                <input id="passenger_valid_id" name="passenger_valid_id" type="file" accept="image/jpeg,image/png">
                <small class="muted">JPG or PNG, up to 5 MB.</small>
            </div>
            <div id="driverAttachments" class="stack" hidden>
                <div class="required-documents">
                    <strong>List of required documents:</strong>
                    <br>
                    <p>Driver Applicant's are required to upload/provide the documents below: </p>
                    <p>( Please ensure that the documents are readable, with clarity. ) </p>
                    <ul>
                        <li>Driver's License</li>
                        <li>Valid government-issued ID</li>
                        <li>OR/CR</li>
                        <li>Third-Party Liability (CTPL) insurance</li>
                        <li>Motor Vehicle Inspection Report (MVIR)</li>
                        <li>Emission Compliance Certificate</li>
                    </ul>
                </div>
                <button id="upload-documents" class="btn btn-upload-documents" type="button">Upload Documents</button>
            </div>
            <button id="signup-submit" class="btn" type="submit">Create passenger account</button>
        </form>
        <p class="auth-foot">Already registered? <a href="<?= htmlspecialchars(web_url('signin.php')) ?>">Sign in</a></p>
    </div>
</div>
<div id="driver-documents-modal" class="modal auth-upload-modal" hidden>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="driver-documents-title">
        <div class="modal-head">
            <div>
                <p class="landing-eyebrow">DRIVER VERIFICATION</p>
                <h2 id="driver-documents-title">Upload documents</h2>
            </div>
            <button id="close-driver-documents" class="icon-btn" type="button" aria-label="Close upload documents dialog">×</button>
        </div>
        <p class="auth-upload-help">Upload JPG or PNG files, up to 5 MB each.</p>
        <div class="auth-upload-fields">
            <div>
                <label for="license">Driver's License</label>
                <input id="license" name="license" form="signup-form" type="file" accept="image/jpeg,image/png">
            </div>
            <div>
                <label for="valid_id">Valid government-issued ID</label>
                <input id="valid_id" name="valid_id" form="signup-form" type="file" accept="image/jpeg,image/png">
            </div>
            <div>
                <label for="or_cr">OR/CR</label>
                <input id="or_cr" name="or_cr" form="signup-form" type="file" accept="image/jpeg,image/png">
            </div>
            <div>
                <label for="ctpl">Third-Party Liability (CTPL) insurance</label>
                <input id="ctpl" name="ctpl" form="signup-form" type="file" accept="image/jpeg,image/png">
            </div>
            <div>
                <label for="mvir">Motor Vehicle Inspection Report (MVIR)</label>
                <input id="mvir" name="mvir" form="signup-form" type="file" accept="image/jpeg,image/png">
            </div>
            <div>
                <label for="emission">Emission Compliance Certificate</label>
                <input id="emission" name="emission" form="signup-form" type="file" accept="image/jpeg,image/png">
            </div>
        </div>
        <button id="save-driver-documents" class="btn" type="button">Save Documents</button>
    </div>
</div>
<?php public_layout_end(['signup.js?v=4']); ?>
