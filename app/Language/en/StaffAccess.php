<?php

/*
 * English text for staff sign-in help: "Forgot password", choosing a new
 * password from an emailed link, and "Request an account".
 */
return [
    // Sign-in page links
    'forgotLink'        => 'Forgot your password?',
    'needAccount'       => 'No staff account yet?',
    'requestLink'       => 'Request an account',
    'backToSignIn'      => 'Back to sign-in',
    'rememberHint'      => 'Keeps you signed in on this device for up to {0} days. Do not use on shared computers.',

    // Forgot password
    'forgotTitle'       => 'Forgot your password?',
    'forgotIntro'       => 'Enter the email address you use to sign in. We will email you a link to choose a new password. The link works once, for one hour.',
    'email'             => 'Email address',
    'sendLink'          => 'Email me a link',
    'forgotSent'        => 'If that address belongs to a staff account, a link to choose a new password is on its way. It works once, for one hour. Check your spam folder if it does not arrive within a few minutes.',
    'tooMany'           => 'Too many attempts. Please wait an hour and try again.',

    // Choose a new password
    'resetTitle'        => 'Choose a new password',
    'resetIntro'        => 'Choose a password that you do not use anywhere else.',
    'resetHint'         => 'At least 8 characters. Avoid your name, email address and common words.',
    'newPassword'       => 'New password',
    'confirmPassword'   => 'Type the new password again',
    'savePassword'      => 'Save password',
    'linkInvalid'       => 'This link has expired or has already been used. Ask for a new one.',
    'passwordSaved'     => 'Your password has been saved. Sign in with it now. You will also be asked for the code we email you.',
    'passwordsDiffer'   => 'The two passwords do not match.',

    // Request an account
    'requestTitle'      => 'Request a staff account',
    'requestIntro'      => 'For Ministry staff who need to work on this website. A Super Administrator checks every request. You cannot sign in until your request is approved, and they decide what you can do.',
    'fullName'          => 'Full name',
    'workEmail'         => 'Work email address',
    'office'            => 'Department, directorate or mission',
    'jobTitle'          => 'Job title',
    'reason'            => 'What do you need to do on the website?',
    'sendRequest'       => 'Send request',
    'requestSent'       => 'Thank you. Your request has been sent to the website\'s Super Administrators. If it is approved, you will receive an email with a link to choose your password.',

    // Emails (plain text)
    'resetEmailSubject' => 'Choose a new password for the MOFAIC website',
    'resetEmailBody'    => "Someone asked to reset the password of your staff account on the MOFAIC website.\n\nTo choose a new password, open this link within one hour:\n{0}\n\nThe link works once. If you did not ask for this, ignore this email; your password has not changed.",
    'changedEmailSubject' => 'Your MOFAIC website password was changed',
    'changedEmailBody'  => "The password of your staff account on the MOFAIC website was just changed, and you were signed out on every device.\n\nIf you did not do this, contact a Super Administrator straight away.",
    'newRequestEmailSubject' => 'New staff account request',
    'newRequestEmailBody' => "Someone has asked for a staff account on the MOFAIC website. Review the request on the staff accounts screen:\n{0}",
    'approvedEmailSubject' => 'Your MOFAIC website staff account',
    'approvedEmailBody' => "Your request for a staff account on the MOFAIC website has been approved.\n\nChoose your password by opening this link within 48 hours:\n{0}\n\nThen sign in at {1}. Each time you sign in, we will also email you a code.",
    'declinedEmailSubject' => 'Your MOFAIC website staff account request',
    'declinedEmailBody' => "Your request for a staff account on the MOFAIC website was not approved. If you think this is a mistake, contact your Super Administrator.",
];
