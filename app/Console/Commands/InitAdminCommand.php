<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class InitAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:init {--name=} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize admin account';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Initializing Admin Account...');
        $this->newLine();

        // 检查是否已存在admin账号
        if (Admin::count() > 0) {
            $this->warn('⚠️  Admin accounts already exist!');
            
            if (!$this->confirm('Do you want to create another admin account?')) {
                $this->info('Operation cancelled.');
                return;
            }
        }

        // 获取用户名
        $name = $this->option('name');
        if (!$name) {
            $name = $this->ask('Enter admin username', 'admin');
        }

        // 获取密码
        $password = $this->option('password');
        if (!$password) {
            $password = $this->secret('Enter admin password');
        }

        // 验证输入
        $validator = Validator::make([
            'name' => $name,
            'password' => $password,
        ], [
            'name' => 'required|string|min:3|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            $this->error('❌ Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error("   - {$error}");
            }
            return;
        }

        // 检查用户名是否已存在
        if (Admin::where('name', $name)->exists()) {
            $this->error("❌ Admin with username '{$name}' already exists!");
            return;
        }

        try {
            // 创建admin账号
            $admin = Admin::create([
                'name' => $name,
                'password' => Hash::make($password),
            ]);

            $this->info('✅ Admin account created successfully!');
            $this->newLine();
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $admin->id],
                    ['Username', $admin->name],
                    ['Created At', $admin->created_at->format('Y-m-d H:i:s')],
                ]
            );

            $this->newLine();
            $this->info('🔑 You can now login using:');
            $this->line("   Username: {$name}");
            $this->line("   Password: [your password]");

        } catch (\Exception $e) {
            $this->error('❌ Failed to create admin account: ' . $e->getMessage());
        }
    }
}