<?php

/*
 * English interface text for public forms and SSD DIASPORA. Legal and policy
 * text (privacy notice, voluntary-registration statement, headquarters
 * contact) is not here: Ministry Administrators enter the approved wording
 * as fixed text blocks.
 */
return [
    // Shared
    'required'          => 'required',
    'optional'          => 'optional',
    'send'              => 'Send',
    'errorsHeading'     => 'Please correct the following:',
    'formClosed'        => 'This form is not open yet. It opens once the Ministry has published its privacy notice.',
    'privacyHeading'    => 'How we handle your information',
    'freeTextWarning'   => 'Do not include passport, national ID, bank or other identity or financial numbers.',
    'referenceIs'       => 'Your reference is {0}. Keep it if you contact the Ministry about this.',
    'honeypotLabel'     => 'Leave this field empty',
    'noFiles'           => 'Files cannot be sent with this form.',
    'notSent'           => 'Your message could not be sent. Please try again.',
    'expired'           => 'This form has expired. Please reload the page and try again.',
    'tooFast'           => 'That was very quick. Please check your entries and send the form again.',
    'tooMany'           => 'Too many forms have been sent from your connection. Please try again later.',
    'emailOrPhone'      => 'Give an email address or a telephone number, or both.',
    'chooseListed'      => 'Choose one of the listed options for {0}.',
    'newsletterNeedsEmail' => 'Give an email address to receive the newsletter.',

    // Fields
    'name'              => 'Name',
    'fullName'          => 'Full name',
    'email'             => 'Email',
    'phone'             => 'Telephone',
    'subject'           => 'Subject',
    'message'           => 'Message',
    'address'           => 'Full address',
    'city'              => 'City',
    'locality'          => 'Locality or area',
    'countryOfResidence'=> 'Country of residence',
    'chooseCountry'     => 'Choose a country',
    'inquiryNature'     => 'Nature of your inquiry',
    'consularHelp'      => 'Consular help you would like',
    'category'          => 'Category',
    'chooseCategory'    => 'Choose a category',
    'newsletterOptIn'   => 'Also send me the diaspora newsletter by email. This is separate from registration and you can unsubscribe at any time.',

    // CONTACTS
    'inquiryHeading'    => 'Send an inquiry',
    'inquiryIntro'      => 'Use this form for general questions to the Ministry. It cannot be used to apply for visas or consular services.',
    'inquiryThanks'     => 'Thank you. Your inquiry has been received.',

    // SSD DIASPORA
    'diasporaIntro'     => 'Information and services for South Sudanese living abroad.',
    'register'          => 'Register',
    'registerIntro'     => 'Tell us where you live so you can find the missions that serve your country.',
    'contribute'        => 'Contribute',
    'contributeIntro'   => 'Share ideas, feedback, development proposals or investment information.',
    'stayInformed'      => 'Stay Informed',
    'stayInformedIntro' => 'Diaspora announcements, events and newsletters.',
    'byCountry'         => 'Diaspora by Country',
    'byCountryIntro'    => 'Information organised by country of residence.',
    'registerHeading'   => 'Diaspora registration',
    'chooseMissionHeading' => 'Choose a mission',
    'chooseMissionIntro'   => 'These missions serve {0}. Choose the one you would like to contact.',
    'chooseMission'     => 'Choose this mission',
    'noMissionHeading'  => 'No mission is listed for {0}',
    'noMissionIntro'    => 'Your registration has been saved. No South Sudanese mission is currently listed as serving this country.',
    'confirmationHeading' => 'Registration complete',
    'confirmationIntro' => 'You chose the mission below. Your details have not been sent to it. Please contact the mission directly using the details below.',
    'missionProfile'    => 'Mission profile',
    'nonResident'       => 'Non-resident mission',
    'contributeHeading' => 'Make a contribution',
    'contributeAnonymous' => 'You can contribute anonymously. Name, email and telephone are optional.',
    'contributeNoPayments' => 'This form does not accept payments or financial transactions.',
    'contributeThanks'  => 'Thank you. Your contribution has been received.',
    'announcements'     => 'Announcements',
    'events'            => 'Events',
    'newsletters'       => 'Newsletters',
    'allUpdates'        => 'All updates',
    'subscribeHeading'  => 'Subscribe to the diaspora newsletter',
    'subscribeConsent'  => 'Yes, send me the diaspora newsletter by email.',
    'subscribe'         => 'Subscribe',
    'subscribeConsentRequired' => 'Tick the box to confirm you want the newsletter.',
    'subscribeThanks'   => 'Check your email. If the address can receive the newsletter, we have sent a link to confirm your subscription.',
    'confirmedHeading'  => 'Subscription confirmed',
    'confirmedText'     => 'You will now receive the diaspora newsletter. Every issue includes a link to unsubscribe.',
    'confirmFailed'     => 'This confirmation link is not valid or has expired. Please subscribe again.',
    'unsubscribeHeading'=> 'Unsubscribe from the diaspora newsletter',
    'unsubscribeButton' => 'Unsubscribe',
    'unsubscribedText'  => 'You have been unsubscribed. You will not receive further newsletters.',
    'unsubscribeFailed' => 'This unsubscribe link is not valid.',
    'missionsServing'   => 'Missions serving {0}',

    // Emails (plain text; no personal details beyond the recipient's own address)
    'newsletterConfirmSubject' => 'Confirm your diaspora newsletter subscription',
    'newsletterConfirmBody'    => "Please confirm that you want to receive the SSD Diaspora newsletter by opening this link:\n\n{0}\n\nIf you did not ask for this, ignore this email and you will not be subscribed.",
    'newsletterReadOnline'     => 'Read it on the website: {0}',
    'newsletterUnsubscribe'    => 'To stop receiving this newsletter: {0}',
];
