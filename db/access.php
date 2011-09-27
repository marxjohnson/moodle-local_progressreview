<?php

$capabilities = array(

    // Create new sessions and generate reviews for classes
    'moodle/local_progressreview:manage' => array(
        'captype' => 'write',
        'riskbitmask' => RISK_MANAGETRUST|RISK_PERSONAL,
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

    // View all reviews within a category/department
    'moodle/local_progressreview:viewall' => array(
        'captype' => 'read',
        'riskbitmask' => RISK_PERSONAL,
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW
        )
    ),

    // View all reviews within a course
    'moodle/local_progressreview:view' => array(
        'captype' => 'read',
        'riskbitmask' => RISK_PERSONAL,
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
        )
    ),

    // View own reviews
    'moodle/local_progressreview:viewown' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => array(
            'student' => CAP_ALLOW
        )
    ),

    // Write reviews within a course
    'moodle/local_progressreview:write' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW
        )
    ),

    // Transfer an existing review to a different teacher
    'moodle/local_progressreview:transfer' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW
        )
    )
);
