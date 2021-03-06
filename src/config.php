<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

/**
 * Campaign config.php
 *
 * This file exists only as a template for the Campaign settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'campaign.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    // Setting to true will save email messages into local files (in storage/runtime/debug/mail) rather than actually sending them
    //'testMode' => false,

    // An API key to use for triggering tasks and notifications (min. 16 characters)
    //'apiKey' => 'aBcDeFgHiJkLmNoP',

    // The from names and emails that sendouts can be sent from
    //'fromNamesEmails' => [['Zorro','legend@zorro.com'], ['Don Diego','dondiego@zorro.com']],

    // A label to use for the email field
    //'emailFieldLabel' => 'Email',

    // Enable reCAPTCHA to protect mailing list subscription forms from bots
    //'reCaptcha' => false,

    // The reCAPTCHA site key
    //'reCaptchaSiteKey' => 'aBcDeFgHiJkLmNoPaBcDeFgHiJkLmNoP',

    // The reCAPTCHA secret key
    //'reCaptchaSecretKey' => 'aBcDeFgHiJkLmNoPaBcDeFgHiJkLmNoP',

    // The reCAPTCHA error message
    //'reCaptchaErrorMessage' => 'Your form submission was blocked. Please go back and verify that you are human.',

    // The size of the reCAPTCHA widget
    // 'reCaptchaSize' => 'normal',

    // The color theme of the reCAPTCHA widget
    // 'reCaptchaTheme' => 'light',

    // The position of the reCAPTCHA badge (when invisible)
    // 'reCaptchaBadge' => 'bottomright',

    // The maximum number of pending contacts to store per email address and mailing list
    //'maxPendingContacts' => 5,

    // The amount of time to wait before purging pending contacts in seconds or as an interval (0 for disabled)
    //'purgePendingContactsDuration' => 0,

    // The threshold for memory usage per sendout batch as a fraction
    //'memoryThreshold' => 0.8,

    // The threshold for execution time per sendout batch as a fraction
    //'timeThreshold' => 0.8,

    // The memory usage limit per sendout batch in bytes or a shorthand byte value (set to -1 for unlimited)
    //'memoryLimit' => '1024M',

    // The execution time limit per sendout batch in seconds (set to 0 for unlimited)
    //'timeLimit' => 300,

    // The maximum size of sendout batches
    //'maxBatchSize' => 1000,

    // The maximum number of sendout retry attempts
    //'maxRetryAttempts' => 10,

    // The amount of time in seconds to delay jobs between sendout batches
    //'batchJobDelay' => 10,
];
