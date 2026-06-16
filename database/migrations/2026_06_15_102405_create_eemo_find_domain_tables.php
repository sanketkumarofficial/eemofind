<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 20)->nullable()->unique()->after('name');
            $table->string('profile_image')->nullable()->after('password');
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('last_login_at')->nullable()->index();
            $table->string('last_login_ip', 45)->nullable();
            $table->string('theme', 10)->default('light');
            $table->string('referral_code', 20)->nullable()->unique();
            $table->timestamp('force_logout_at')->nullable();
            $table->softDeletes();
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('imei', 32)->unique();
            $table->string('device_type', 50);
            $table->string('model')->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('sim_number', 30)->nullable()->index();
            $table->boolean('is_enabled')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'is_enabled']);
        });

        Schema::create('device_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('speed', 8, 2)->default(0);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('heading', 6, 2)->nullable();
            $table->unsignedTinyInteger('battery')->nullable();
            $table->string('network', 30)->nullable();
            $table->string('gps_status', 20)->nullable();
            $table->string('motion_status', 20)->default('idle');
            $table->boolean('is_online')->default(false)->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('location_recorded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('member')->index();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();
            $table->unique(['group_id', 'user_id']);
            $table->index(['user_id', 'role']);
        });

        Schema::create('pairing_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('used_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['group_id', 'is_active']);
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('duration_days');
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'end_date']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway')->default('razorpay');
            $table->string('order_id')->unique();
            $table->string('payment_id')->nullable()->unique();
            $table->string('signature')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('status', 20)->default('pending')->index();
            $table->string('payment_method', 40)->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'created_at']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->json('data');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('module', 50)->index();
            $table->string('action', 50)->index();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->string('ip_address', 45)->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['module', 'action', 'created_at']);
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 40)->index();
            $table->string('priority', 20)->default('medium')->index();
            $table->string('status', 30)->default('open')->index();
            $table->string('subject');
            $table->longText('description');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['assigned_to', 'status', 'priority']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->longText('message');
            $table->boolean('is_internal')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('reply_id')->nullable()->constrained('ticket_replies')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('category')->nullable()->index();
            $table->string('question');
            $table->longText('answer');
            $table->boolean('is_published')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->index();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });

        Schema::create('sos_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('firebase_key')->nullable()->unique();
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('triggered_at')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
        });

        Schema::create('geofences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('shape', 20)->default('circle');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meters')->nullable();
            $table->json('polygon')->nullable();
            $table->boolean('notify_entry')->default(true);
            $table->boolean('notify_exit')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('geofence_users', function (Blueprint $table) {
            $table->foreignId('geofence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['geofence_id', 'user_id']);
        });

        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('mobile', 20);
            $table->string('relationship', 50);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'is_primary']);
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('reward_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('push_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 512)->unique();
            $table->string('platform', 20)->index();
            $table->string('device_name')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20)->index();
            $table->string('event_type', 80)->index();
            $table->string('title');
            $table->text('message');
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('report_type', 50)->index();
            $table->string('format', 10);
            $table->json('filters')->nullable();
            $table->string('status', 20)->default('queued')->index();
            $table->string('file_path')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        foreach (['report_exports', 'notification_logs', 'push_tokens', 'referrals',
            'emergency_contacts', 'geofence_users', 'geofences', 'sos_events', 'settings', 'faqs',
            'ticket_attachments', 'ticket_replies', 'support_tickets', 'activity_logs', 'notifications',
            'payments', 'subscriptions', 'plans', 'pairing_codes', 'group_members', 'groups',
            'device_snapshots', 'devices'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['mobile', 'profile_image', 'gender', 'date_of_birth', 'status',
                'last_login_at', 'last_login_ip', 'theme', 'referral_code', 'force_logout_at']);
        });
    }
};
