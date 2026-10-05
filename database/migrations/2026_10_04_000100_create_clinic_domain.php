<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->text('address')->nullable();
            $t->string('phone')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('clinic_id')->nullable()->constrained();
            $t->boolean('active')->default(true);
        });
        Schema::create('polyclinics', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->string('name');
            $t->string('code');
            $t->boolean('active')->default(true);
            $t->unique(['clinic_id', 'code']);
            $t->timestamps();
        });
        Schema::create('medical_staff', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('name');
            $t->string('profession');
            $t->string('license')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('doctor_schedules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('medical_staff_id')->constrained('medical_staff');
            $t->foreignId('polyclinic_id')->constrained();
            $t->unsignedTinyInteger('weekday');
            $t->time('starts_at');
            $t->time('ends_at');
            $t->unsignedInteger('capacity')->default(30);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('service_tariffs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->string('name');
            $t->string('kind')->default('consultation');
            $t->decimal('price', 15, 2);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('diagnoses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->string('code');
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->unique(['clinic_id', 'code']);
            $t->timestamps();
        });
        Schema::create('medical_procedures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->string('name');
            $t->decimal('price', 15, 2);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('medicine_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('units', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->string('name');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->string('name');
            $t->string('phone')->nullable();
            $t->text('address')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('payment_methods', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->string('name');
            $t->string('code');
            $t->boolean('active')->default(true);
            $t->unique(['clinic_id', 'code']);
            $t->timestamps();
        });
        Schema::create('medicines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->string('sku');
            $t->string('name');
            $t->string('generic_name')->nullable();
            $t->foreignId('medicine_category_id')->constrained();
            $t->foreignId('unit_id')->constrained();
            $t->string('form')->nullable();
            $t->string('strength')->nullable();
            $t->decimal('purchase_price', 15, 2)->default(0);
            $t->decimal('selling_price', 15, 2);
            $t->unsignedInteger('minimum_stock')->default(10);
            $t->boolean('active')->default(true);
            $t->unique(['clinic_id', 'sku']);
            $t->timestamps();
        });
        Schema::create('patients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->string('medical_number')->unique();
            $t->string('nik', 16)->nullable()->unique();
            $t->string('name')->index();
            $t->string('birth_place')->nullable();
            $t->date('birth_date');
            $t->string('sex', 1);
            $t->string('phone')->nullable()->index();
            $t->text('address')->nullable();
            $t->string('blood_type')->nullable();
            $t->text('allergies')->nullable();
            $t->string('emergency_contact')->nullable();
            $t->string('payer')->default('Umum');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('number_sequences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->string('key');
            $t->unsignedBigInteger('value')->default(0);
            $t->unique(['clinic_id', 'key']);
            $t->timestamps();
        });
        Schema::create('visits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->string('number')->unique();
            $t->foreignId('patient_id')->constrained();
            $t->foreignId('polyclinic_id')->constrained();
            $t->foreignId('medical_staff_id')->constrained('medical_staff');
            $t->foreignId('service_tariff_id')->constrained();
            $t->date('visit_date');
            $t->string('payer')->default('Umum');
            $t->string('status')->default('waiting_triage');
            $t->timestamp('completed_at')->nullable();
            $t->text('cancellation_reason')->nullable();
            $t->index(['clinic_id', 'visit_date', 'status']);
            $t->timestamps();
        });
        Schema::create('queues', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('visit_id')->unique()->constrained();
            $t->foreignId('polyclinic_id')->constrained();
            $t->date('queue_date');
            $t->unsignedInteger('number');
            $t->string('status')->default('waiting');
            $t->timestamp('called_at')->nullable();
            $t->timestamp('consultation_at')->nullable();
            $t->unique(['clinic_id', 'polyclinic_id', 'queue_date', 'number'], 'queue_number_unique');
            $t->timestamps();
        });
        Schema::create('vital_signs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('visit_id')->unique()->constrained();
            $t->foreignId('user_id')->constrained();
            $t->text('complaint');
            $t->unsignedSmallInteger('systolic');
            $t->unsignedSmallInteger('diastolic');
            $t->decimal('temperature', 4, 1);
            $t->decimal('weight', 6, 2);
            $t->decimal('height', 6, 2);
            $t->decimal('bmi', 6, 2);
            $t->unsignedSmallInteger('pulse');
            $t->unsignedSmallInteger('respiration');
            $t->unsignedSmallInteger('oxygen');
            $t->text('allergies')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('medical_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('visit_id')->unique()->constrained();
            $t->foreignId('user_id')->constrained();
            $t->text('subjective');
            $t->text('objective');
            $t->text('assessment');
            $t->text('plan');
            $t->text('history')->nullable();
            $t->text('physical_exam')->nullable();
            $t->text('advice')->nullable();
            $t->date('control_date')->nullable();
            $t->string('status')->default('draft');
            $t->timestamp('finalized_at')->nullable();
            $t->timestamps();
        });
        Schema::create('medical_record_diagnoses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('medical_record_id')->constrained();
            $t->foreignId('diagnosis_id')->constrained();
            $t->boolean('primary')->default(false);
            $t->unique(['medical_record_id', 'diagnosis_id'], 'record_diagnosis_unique');
            $t->timestamps();
        });
        Schema::create('medical_record_procedures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('medical_record_id')->constrained();
            $t->foreignId('medical_procedure_id')->constrained();
            $t->string('description');
            $t->decimal('price', 15, 2);
            $t->unsignedInteger('quantity')->default(1);
            $t->timestamps();
        });
        Schema::create('medical_record_amendments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('medical_record_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->text('reason');
            $t->text('content');
            $t->timestamps();
        });
        Schema::create('prescriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('visit_id')->unique()->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('status')->default('draft');
            $t->timestamp('dispensed_at')->nullable();
            $t->foreignId('dispensed_by')->nullable()->constrained('users');
            $t->text('reason')->nullable();
            $t->timestamps();
        });
        Schema::create('prescription_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('prescription_id')->constrained();
            $t->foreignId('medicine_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->string('dose');
            $t->string('frequency');
            $t->string('duration');
            $t->string('instructions');
            $t->string('unit');
            $t->text('notes')->nullable();
            $t->unique(['prescription_id', 'medicine_id']);
            $t->timestamps();
        });
        Schema::create('purchases', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->string('number')->unique();
            $t->foreignId('supplier_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->date('received_on');
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('medicine_batches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('medicine_id')->constrained();
            $t->foreignId('purchase_id')->nullable()->constrained();
            $t->string('batch_number');
            $t->date('expires_on');
            $t->decimal('purchase_price', 15, 2);
            $t->unsignedInteger('quantity')->default(0);
            $t->string('condition')->default('usable');
            $t->unique(['clinic_id', 'medicine_id', 'batch_number', 'condition'], 'batch_unique');
            $t->index(['clinic_id', 'medicine_id', 'expires_on']);
            $t->timestamps();
        });
        Schema::create('purchase_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('purchase_id')->constrained();
            $t->foreignId('medicine_batch_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->decimal('price', 15, 2);
            $t->timestamps();
        });
        Schema::create('dispense_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('prescription_item_id')->constrained();
            $t->foreignId('medicine_batch_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->unsignedInteger('returned_quantity')->default(0);
            $t->timestamps();
        });
        Schema::create('stock_opnames', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->text('reason');
            $t->timestamps();
        });
        Schema::create('stock_opname_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('stock_opname_id')->constrained();
            $t->foreignId('medicine_batch_id')->constrained();
            $t->unsignedInteger('expected_quantity');
            $t->unsignedInteger('counted_quantity');
            $t->timestamps();
        });
        Schema::create('stock_returns', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('medicine_batch_id')->constrained();
            $t->foreignId('dispense_allocation_id')->nullable()->constrained();
            $t->foreignId('user_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->string('kind');
            $t->text('reason');
            $t->timestamps();
        });
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('medicine_batch_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->integer('quantity');
            $t->string('kind');
            $t->string('source_key')->unique();
            $t->text('reason');
            $t->index(['clinic_id', 'created_at']);
            $t->timestamps();
        });
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('visit_id')->constrained();
            $t->string('number')->unique();
            $t->string('status')->default('draft');
            $t->decimal('subtotal', 15, 2)->default(0);
            $t->decimal('discount', 15, 2)->default(0);
            $t->decimal('total', 15, 2)->default(0);
            $t->text('discount_reason')->nullable();
            $t->text('void_reason')->nullable();
            $t->foreignId('replaces_id')->nullable()->constrained('invoices');
            $t->foreignId('stock_return_id')->nullable()->constrained();
            $t->timestamp('issued_at')->nullable();
            $t->index(['clinic_id', 'status']);
            $t->timestamps();
        });
        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('invoice_id')->constrained();
            $t->string('source_key');
            $t->string('description');
            $t->unsignedInteger('quantity');
            $t->decimal('unit_price', 15, 2);
            $t->decimal('total', 15, 2);
            $t->unique(['invoice_id', 'source_key']);
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('invoice_id')->constrained();
            $t->foreignId('payment_method_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('number')->unique();
            $t->uuid('idempotency_key')->unique();
            $t->decimal('amount', 15, 2);
            $t->decimal('received', 15, 2);
            $t->decimal('change', 15, 2);
            $t->string('reference')->nullable();
            $t->timestamp('paid_at');
            $t->timestamps();
        });
        Schema::create('payment_voids', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('payment_id')->unique()->constrained();
            $t->foreignId('user_id')->constrained();
            $t->decimal('amount', 15, 2);
            $t->text('reason');
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('action');
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->text('reason')->nullable();
            $t->index(['clinic_id', 'created_at']);
            $t->timestamps();
        });
        Schema::create('login_histories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('ip', 45)->nullable();
            $t->timestamps();
        });
        Schema::create('report_exports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('report');
            $t->string('format');
            $t->json('filters');
            $t->string('status')->default('pending');
            $t->string('path')->nullable();
            $t->text('error')->nullable();
            $t->timestamps();
        });
        Schema::create('inventory_alerts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clinic_id')->constrained();
            $t->string('key');
            $t->string('message');
            $t->string('severity');
            $t->unique(['clinic_id', 'key']);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_alerts');
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('login_histories');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('payment_voids');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_returns');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('dispense_allocations');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('medicine_batches');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medical_record_amendments');
        Schema::dropIfExists('medical_record_procedures');
        Schema::dropIfExists('medical_record_diagnoses');
        Schema::dropIfExists('medical_records');
        Schema::dropIfExists('vital_signs');
        Schema::dropIfExists('queues');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('number_sequences');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('medicines');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('units');
        Schema::dropIfExists('medicine_categories');
        Schema::dropIfExists('medical_procedures');
        Schema::dropIfExists('diagnoses');
        Schema::dropIfExists('service_tariffs');
        Schema::dropIfExists('doctor_schedules');
        Schema::dropIfExists('medical_staff');
        Schema::dropIfExists('polyclinics');
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('clinic_id');
            $t->dropColumn('active');
        });
        Schema::dropIfExists('clinics');
    }
};
