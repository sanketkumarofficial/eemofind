<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedInteger('device_limit')->default(1);
            $table->unsignedInteger('group_limit')->default(1);
            $table->unsignedInteger('history_days')->default(30);
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 3)->default('INR');
            $table->string('billing_cycle')->default('monthly');
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('imei')->unique();
            $table->string('sim_number')->nullable()->index();
            $table->string('status')->default('offline')->index();
            $table->decimal('last_latitude', 10, 7)->nullable();
            $table->decimal('last_longitude', 10, 7)->nullable();
            $table->decimal('last_speed', 8, 2)->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['owner_id', 'status']);
        });

        Schema::create('device_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed', 8, 2)->default(0);
            $table->decimal('heading', 8, 2)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->unsignedTinyInteger('battery')->nullable();
            $table->timestamp('recorded_at')->index();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['device_id', 'recorded_at']);
        });

        Schema::create('tracking_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('active')->index();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->index(['owner_id', 'status']);
        });

        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracking_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['tracking_group_id', 'user_id']);
        });

        Schema::create('pairing_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracking_group_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('purpose')->default('group_join');
            $table->unsignedInteger('max_uses')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('usage_history')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway')->default('razorpay')->index();
            $table->string('gateway_order_id')->nullable()->index();
            $table->string('gateway_payment_id')->nullable()->index();
            $table->string('gateway_signature')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('status')->index();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->string('category')->index();
            $table->string('priority')->default('medium')->index();
            $table->string('status')->default('open')->index();
            $table->text('message');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'priority']);
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_reply_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->unsignedBigInteger('size');
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event')->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->index();
            $table->string('key');
            $table->json('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
            $table->unique(['group', 'key']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->longText('answer');
            $table->string('category')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('sos_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tracking_group_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status')->default('open')->index();
            $table->text('message')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('geofences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracking_group_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('circle');
            $table->decimal('center_latitude', 10, 7)->nullable();
            $table->decimal('center_longitude', 10, 7)->nullable();
            $table->unsignedInteger('radius_meters')->nullable();
            $table->json('polygon')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('alert_channels')->nullable();
            $table->timestamps();
        });

        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('relationship')->nullable();
            $table->boolean('notify_sos')->default(true);
            $table->timestamps();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code')->index();
            $table->string('status')->default('pending')->index();
            $table->decimal('reward_amount', 12, 2)->default(0);
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('push_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('platform')->index();
            $table->string('device_name')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel')->index();
            $table->string('target')->index();
            $table->string('title');
            $table->text('body');
            $table->string('status')->index();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (array_reverse([
            'notification_logs','push_tokens','referrals','emergency_contacts','geofences','sos_events',
            'faqs','settings','activity_logs','admin_notifications','ticket_attachments','ticket_replies',
            'support_tickets','payments','pairing_codes','group_members','tracking_groups','device_snapshots',
            'devices','subscriptions','plans',
        ]) as $table) {
            Schema::dropIfExists($table);
        }
    }
};