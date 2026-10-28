<?php

namespace Database\Seeders;

use App\Models\SupportMail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tickets = [
            ['subject' => 'Refund request for order HD-1048', 'body' => 'I returned the desk organizer last week, but I have not seen the refund come through yet. Could you check on it?', 'customer_name' => 'Maya Chen', 'category' => 'refund'],
            ['subject' => 'Package has not arrived', 'body' => 'My parcel was expected on Tuesday and tracking has not updated since the weekend. Do you have a new estimate?', 'customer_name' => 'Elliot Brooks', 'category' => 'delay'],
            ['subject' => 'How do I update my delivery address?', 'body' => 'I just moved and cannot find where to change the address for my next order. Can you point me to the right place?', 'customer_name' => 'Sam Rivera', 'category' => 'how-to'],
            ['subject' => 'Question about a duplicate charge', 'body' => 'I noticed what looks like two charges for the same order on my statement. Would you help me understand what happened?', 'customer_name' => 'Jordan Lee', 'category' => 'billing'],
            ['subject' => 'A feature idea for shared lists', 'body' => 'It would be really helpful if two people could edit the same saved list. Is that something your team might consider?', 'customer_name' => 'Taylor Morgan', 'category' => 'feature'],
            ['subject' => 'I need help with my account', 'body' => 'Something is not working the way I expected and I am not sure where to start. Could someone help me figure it out?', 'customer_name' => 'Alex Kim', 'category' => 'unknown'],
        ];

        foreach ($tickets as $ticket) {
            SupportMail::query()->updateOrCreate(
                ['subject' => $ticket['subject']],
                $ticket,
            );
        }
    }
}
