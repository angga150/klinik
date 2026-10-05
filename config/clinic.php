<?php

return [
    'roles' => [
        'Super Admin' => ['dashboard.view', 'patients.view', 'patients.create', 'patients.update', 'visits.view', 'visits.register', 'queues.manage', 'vitals.create', 'medical-records.view', 'medical-records.write', 'medical-records.finalize', 'prescriptions.view', 'prescriptions.dispense', 'inventory.view', 'inventory.receive', 'inventory.adjust', 'invoices.view', 'invoices.discount', 'payments.create', 'payments.void', 'reports.view', 'users.manage', 'settings.manage', 'audit.view'],
        'Admin Klinik' => ['dashboard.view', 'patients.view', 'patients.create', 'patients.update', 'visits.view', 'visits.register', 'queues.manage', 'inventory.view', 'invoices.view', 'invoices.discount', 'payments.void', 'reports.view', 'settings.manage'],
        'Petugas Pendaftaran' => ['dashboard.view', 'patients.view', 'patients.create', 'patients.update', 'visits.view', 'visits.register', 'queues.manage'],
        'Perawat' => ['dashboard.view', 'patients.view', 'visits.view', 'vitals.create'],
        'Dokter' => ['dashboard.view', 'patients.view', 'visits.view', 'medical-records.view', 'medical-records.write', 'medical-records.finalize', 'prescriptions.view'],
        'Apoteker' => ['dashboard.view', 'visits.view', 'prescriptions.view', 'prescriptions.dispense', 'inventory.view', 'inventory.receive', 'inventory.adjust'],
        'Kasir' => ['dashboard.view', 'visits.view', 'invoices.view', 'payments.create'],
        'Kepala Klinik' => ['dashboard.view', 'reports.view'],
    ],
    'demo_password' => env('DEMO_PASSWORD'),
];
