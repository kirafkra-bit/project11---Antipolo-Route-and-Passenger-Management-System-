<?php
/**
 * LTFRB-style jeepney fare rates for PoloNav.
 *
 * Formula:
 *   if distance <= base_distance_km:
 *       fare = base fare
 *   else:
 *       fare = base fare + ((distance - base_distance_km) * succeeding rate)
 *
 * Traditional jeepney rates were provided by the project.
 * Modern jeepney regular rates were provided.
 *
 * MISSING (do not invent):
 *   - Modern jeepney discounted base fare
 *   - Modern jeepney discounted succeeding rate per km
 *
 * Set those two values when your adviser provides them.
 * Use null until then. The calculator will refuse modern + discounted
 * trips that need a missing rate.
 *
 * Student, Senior Citizen, and PWD all use the "discounted" rates.
 */

return [
    'base_distance_km' => 4,

    'traditional' => [
        'regular' => [
            'base'       => 13.00,
            'succeeding' => 1.80,
        ],
        'discounted' => [
            'base'       => 10.40,
            'succeeding' => 1.44,
        ],
    ],

    'modern' => [
        'regular' => [
            'base'       => 15.00,
            'succeeding' => 2.20,
        ],
        'discounted' => [
            'base'       => null, // not provided — configure later
            'succeeding' => null, // not provided — configure later
            'note'       => 'Modern discounted rates are not yet provided. Update config/fare.php when available.',
        ],
    ],
];
