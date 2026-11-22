<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\MataKuliah;
use App\Models\Kategori;
use App\Models\Tugas;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;

// DATA DEMO LOKAL. Jangan dipakai di production.
// Akun demo (password sama semua: password123)
//   user  : demo.taskmate@example.test   (id 1)
//   user  : other.taskmate@example.test  (id 2)
//   admin : admin.taskmate@example.test  (id 3)
class TaskMateDemoSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        $user = User::firstOrCreate(['email' => 'demo.taskmate@example.test'], [
            'name' => 'Demo TaskMate', 'password' => $password, 'role' => 'user'
        ]);
        $another = User::firstOrCreate(['email' => 'other.taskmate@example.test'], [
            'name' => 'User Lain', 'password' => $password, 'role' => 'user'
        ]);
        $admin = User::firstOrCreate(['email' => 'admin.taskmate@example.test'], [
            'name' => 'Admin TaskMate', 'password' => $password, 'role' => 'admin'
        ]);

        Profile::firstOrCreate(['user_id' => $user->id], [
            'nim' => 'TASKMATE-DEMO', 'no_telepon' => '08000000000',
            'program_studi' => 'Sistem Informasi', 'semester' => 3
        ]);
        $mk = MataKuliah::firstOrCreate(['user_id' => $user->id, 'nama_mata_kuliah' => 'Basis Data'], [
            'dosen' => 'Dosen Demo', 'semester' => '3'
        ]);
        $kat = Kategori::firstOrCreate(['user_id' => $user->id, 'nama_kategori' => 'Praktikum']);
        $tugas = Tugas::firstOrCreate(['user_id' => $user->id, 'judul' => 'Latihan ERD'], [
            'mata_kuliah_id' => $mk->id, 'kategori_id' => $kat->id,
            'deskripsi' => 'Membuat ERD TaskMate', 'deadline' => '2027-01-15 23:59:00', 'status' => 'belum'
        ]);
        $this->command?->info("Demo IDs: userId={$user->id}, otherUserId={$another->id}, adminId={$admin->id}, mataKuliahId={$mk->id}, kategoriId={$kat->id}, tugasId={$tugas->id}");
    }
}