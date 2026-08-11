<?php

declare(strict_types=1);

return [

    'title' => 'Contact',
    'heading' => 'Get in touch',
    'subheading' => 'Tell us what you need. We reply to every enquiry, usually within one working day.',
    'meta_description' => 'Contact ElectroServes in Dar es Salaam for electrical installation, electronics repair and 24/7 emergency callouts.',

    'form' => [
        'heading' => 'Request a quote',
        'submit' => 'Send enquiry',
        'submitting' => 'Sending…',
        'required_note' => 'Fields marked with an asterisk (*) are required.',
        'send_another' => 'Send another enquiry',
    ],

    'fields' => [
        'name' => 'Full name',
        'name_placeholder' => 'e.g. Amina Hassan',
        'email' => 'Email address',
        'email_placeholder' => 'you@example.com',
        'phone' => 'Phone number',
        'phone_placeholder' => '+255 700 000 000',
        'phone_help' => 'Optional — add it if you would prefer us to call you back.',
        'service_type' => 'What do you need help with?',
        'service_placeholder' => 'Select a service',
        'message' => 'Tell us about the job',
        'message_placeholder' => 'Describe the property, the work needed and any deadlines.',
        'message_help' => 'The more detail you give, the more accurate our quote will be.',
        'consent' => 'I agree that ElectroServes may use these details to respond to my enquiry.',
        'consent_link' => 'Read our privacy policy',
    ],

    'validation' => [
        'name_required' => 'Please tell us your name so we know who to reply to.',
        'name_min' => 'Please enter your full name.',
        'email_required' => 'We need an email address to send our reply to.',
        'email_invalid' => 'That email address does not look right. Please check it.',
        'phone_invalid' => 'Please enter a phone number using digits, spaces, brackets, + or -.',
        'service_required' => 'Please choose the service you need.',
        'service_invalid' => 'Please choose one of the listed services.',
        'message_required' => 'Please describe the work you need done.',
        'message_min' => 'Please give us a little more detail — at least 10 characters.',
        'message_max' => 'Please keep your message under 5,000 characters.',
        'consent_required' => 'Please confirm we may use your details to reply to you.',
        'summary_heading' => 'Please fix the following before sending:',
    ],

    'status' => [
        'success_title' => 'Thank you — your enquiry is on its way.',
        'success_body' => 'A member of our team will get back to you within one working day.',
        'error_title' => 'We could not send your enquiry.',
        'error_body' => 'Something went wrong on our side. Please try again, or call us on :phone and we will help you straight away.',
        'error_body_no_phone' => 'Something went wrong on our side. Please try again in a few minutes.',
        'rate_limited_title' => 'That is a few too many enquiries.',
        'rate_limited_body' => 'You have reached the limit for now. Please wait a little while before sending another, or call us on :phone.',
        'rate_limited_body_no_phone' => 'You have reached the limit for now. Please wait a little while before sending another.',
    ],

    'service_other' => 'Something else',

    'sidebar' => [
        'heading' => 'Other ways to reach us',
        'phone_label' => 'Call us',
        'emergency_label' => 'Emergency line (24/7)',
        'email_label' => 'Email us',
        'address_label' => 'Visit us',
        'hours_label' => 'Business hours',
        'map_title' => 'Map showing the ElectroServes office location',
        'map_unavailable' => 'A map of our location is not available right now.',
    ],

    'email' => [
        'subject' => 'New website enquiry from :name',
        'heading' => 'New enquiry from the website',
        'intro' => 'Someone has submitted the contact form on the ElectroServes website.',
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'not_provided' => 'Not provided',
        'service' => 'Service needed',
        'message' => 'Message',
        'submitted_at' => 'Submitted',
        'reply_hint' => 'Reply directly to this email to respond to the sender.',
    ],

];
