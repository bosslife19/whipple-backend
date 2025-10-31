<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AddvirtualUser extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $users = [
            [
                "name" => "Ayo Musa",
                "phone" => "+1000001",
                "email" => "virtual01@whipple.com",
            ],
            [
                "name" => "Maria Gonzalez",
                "phone" => "+1000002",
                "email" => "virtual02@whipple.com",
            ],
            [
                "name" => "Liam O’Connor",
                "phone" => "+1000003",
                "email" => "virtual03@whipple.com",
            ],
            [
                "name" => "Chen Wei",
                "phone" => "+1000004",
                "email" => "virtual04@whipple.com",
            ],
            [
                "name" => "Fatima Ibrahim",
                "phone" => "+1000005",
                "email" => "virtual05@whipple.com",
            ],
            [
                "name" => "John Smith",
                "phone" => "+1000006",
                "email" => "virtual06@whipple.com",
            ],
            [
                "name" => "Priya Patel",
                "phone" => "+1000007",
                "email" => "virtual07@whipple.com",
            ],
            [
                "name" => "Kenji Tanaka",
                "phone" => "+1000008",
                "email" => "virtual08@whipple.com",
            ],
            [
                "name" => "David Cohen",
                "phone" => "+1000009",
                "email" => "virtual09@whipple.com",
            ],
            [
                "name" => "Amara Okafor",
                "phone" => "+1000010",
                "email" => "virtual10@whipple.com",
            ],
            [
                "name" => "Elena Petrova",
                "phone" => "+1000011",
                "email" => "virtual11@whipple.com",
            ],
            [
                "name" => "Carlos Silva",
                "phone" => "+1000012",
                "email" => "virtual12@whipple.com",
            ],
            [
                "name" => "Sara Kim",
                "phone" => "+1000013",
                "email" => "virtual13@whipple.com",
            ],
            [
                "name" => "Ahmed Al-Farsi",
                "phone" => "+1000014",
                "email" => "virtual14@whipple.com",
            ],
            [
                "name" => "James Brown",
                "phone" => "+1000015",
                "email" => "virtual15@whipple.com",
            ],
            [
                "name" => "Nguyen Thi Hoa",
                "phone" => "+1000016",
                "email" => "virtual16@whipple.com",
            ],
            [
                "name" => "Pierre Dupont",
                "phone" => "+1000017",
                "email" => "virtual17@whipple.com",
            ],
            [
                "name" => "Ravi Sharma",
                "phone" => "+1000018",
                "email" => "virtual18@whipple.com",
            ],
            [
                "name" => "Sophia Martinez",
                "phone" => "+1000019",
                "email" => "virtual19@whipple.com",
            ],
            [
                "name" => "William Johnson",
                "phone" => "+1000020",
                "email" => "virtual20@whipple.com",
            ],
        ];

        foreach($users as $usr){
            User::updateOrCreate([
                'email' => $usr['email'],
            ],[
                'name' => $usr['name'],
                'phone' => $usr['phone'],
                'password' => 'demo',
                'referral_code' => 'demo'
            ]);
        }
    }
}
