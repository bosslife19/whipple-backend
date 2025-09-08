<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            [
                'question' => 'What is the capital of France?',
                'options' => ['Paris', 'Berlin', 'Madrid', 'Lisbon'],
                'correct' => 'Paris',
            ],
            [
                'question' => 'Which planet is known as the Red Planet?',
                'options' => ['Earth', 'Mars', 'Jupiter', 'Saturn'],
                'correct' => 'Mars',
            ],
            [
                'question' => 'Who painted the Mona Lisa?',
                'options' => ['Leonardo da Vinci', 'Vincent van Gogh', 'Pablo Picasso', 'Claude Monet'],
                'correct' => 'Leonardo da Vinci',
            ],
            [
                'question' => 'What is the largest ocean on Earth?',
                'options' => ['Atlantic', 'Indian', 'Pacific', 'Arctic'],
                'correct' => 'Pacific',
            ],
            [
                'question' => 'Which gas do plants absorb during photosynthesis?',
                'options' => ['Oxygen', 'Carbon Dioxide', 'Nitrogen', 'Hydrogen'],
                'correct' => 'Carbon Dioxide',
            ],
            [
                'question' => 'Who discovered gravity?',
                'options' => ['Isaac Newton', 'Albert Einstein', 'Galileo Galilei', 'Nikola Tesla'],
                'correct' => 'Isaac Newton',
            ],
            [
                'question' => 'Which continent is the Sahara Desert located in?',
                'options' => ['Asia', 'Africa', 'Australia', 'South America'],
                'correct' => 'Africa',
            ],
            [
                'question' => 'How many players are in a football (soccer) team?',
                'options' => ['9', '10', '11', '12'],
                'correct' => '11',
            ],
            [
                'question' => 'What is H2O commonly known as?',
                'options' => ['Hydrogen', 'Oxygen', 'Water', 'Salt'],
                'correct' => 'Water',
            ],
            [
                'question' => 'Which is the fastest land animal?',
                'options' => ['Cheetah', 'Horse', 'Lion', 'Tiger'],
                'correct' => 'Cheetah',
            ],
            [
                'question' => 'Who wrote “Hamlet”?',
                'options' => ['William Shakespeare', 'Charles Dickens', 'Mark Twain', 'Jane Austen'],
                'correct' => 'William Shakespeare',
            ],
            [
                'question' => 'What is the boiling point of water at sea level?',
                'options' => ['90°C', '100°C', '110°C', '120°C'],
                'correct' => '100°C',
            ],
            [
                'question' => 'Which country hosted the 2018 FIFA World Cup?',
                'options' => ['Brazil', 'Russia', 'Germany', 'South Africa'],
                'correct' => 'Russia',
            ],
            [
                'question' => 'What is the largest mammal in the world?',
                'options' => ['African Elephant', 'Blue Whale', 'Giraffe', 'Orca'],
                'correct' => 'Blue Whale',
            ],
            [
                'question' => 'What is the chemical symbol for Gold?',
                'options' => ['Gd', 'Au', 'Ag', 'Pt'],
                'correct' => 'Au',
            ],
            // Sports
            ['question' => 'How many players are on the field in a standard football (soccer) team?', 'options' => ['9', '10', '11', '12'], 'correct' => '11'],
            ['question' => 'Which country hosted the 2018 FIFA World Cup?', 'options' => ['Brazil', 'Russia', 'Germany', 'South Africa'], 'correct' => 'Russia'],
            ['question' => 'In tennis, what piece of fruit is found at the top of the men’s Wimbledon trophy?', 'options' => ['Apple', 'Banana', 'Pineapple', 'Grapes'], 'correct' => 'Pineapple'],
            ['question' => 'Which athlete is known as the fastest man alive?', 'options' => ['Tyson Gay', 'Yohan Blake', 'Usain Bolt', 'Michael Johnson'], 'correct' => 'Usain Bolt'],
            ['question' => 'How many rings are there on the Olympic flag?', 'options' => ['4', '5', '6', '7'], 'correct' => '5'],

            // Politics
            ['question' => 'Who was the first President of the United States?', 'options' => ['George Washington', 'John Adams', 'Thomas Jefferson', 'Abraham Lincoln'], 'correct' => 'George Washington'],
            ['question' => 'Which British Prime Minister declared “Peace for our time” in 1938?', 'options' => ['Winston Churchill', 'Neville Chamberlain', 'Margaret Thatcher', 'Tony Blair'], 'correct' => 'Neville Chamberlain'],
            ['question' => 'Who is the longest-serving South African President?', 'options' => ['Nelson Mandela', 'Jacob Zuma', 'Thabo Mbeki', 'Cyril Ramaphosa'], 'correct' => 'Jacob Zuma'],
            ['question' => 'The Cold War was mainly between the USA and which other nation?', 'options' => ['Germany', 'China', 'Soviet Union', 'Japan'], 'correct' => 'Soviet Union'],
            ['question' => 'What year did Nigeria gain independence?', 'options' => ['1957', '1960', '1963', '1966'], 'correct' => '1960'],

            // Entertainment
            ['question' => 'Who played Iron Man in the Marvel Cinematic Universe?', 'options' => ['Chris Evans', 'Robert Downey Jr.', 'Mark Ruffalo', 'Chris Hemsworth'], 'correct' => 'Robert Downey Jr.'],
            ['question' => 'Which movie won Best Picture at the 2020 Oscars?', 'options' => ['1917', 'Joker', 'Parasite', 'Ford v Ferrari'], 'correct' => 'Parasite'],
            ['question' => 'In the TV show “Friends”, what is the name of Ross’s second wife?', 'options' => ['Rachel', 'Emily', 'Carol', 'Monica'], 'correct' => 'Emily'],
            ['question' => 'Which animated film features a character called Woody?', 'options' => ['Shrek', 'Frozen', 'Toy Story', 'Cars'], 'correct' => 'Toy Story'],
            ['question' => 'Who directed the film “Inception”?', 'options' => ['Steven Spielberg', 'Christopher Nolan', 'James Cameron', 'Ridley Scott'], 'correct' => 'Christopher Nolan'],

            // Music
            ['question' => 'Who is known as the King of Pop?', 'options' => ['Prince', 'Michael Jackson', 'Elvis Presley', 'Freddie Mercury'], 'correct' => 'Michael Jackson'],
            ['question' => 'Which band released the album “Abbey Road”?', 'options' => ['The Rolling Stones', 'The Beatles', 'Queen', 'Pink Floyd'], 'correct' => 'The Beatles'],
            ['question' => 'What instrument does a pianist play?', 'options' => ['Violin', 'Drums', 'Piano', 'Saxophone'], 'correct' => 'Piano'],
            ['question' => 'Which female artist sang “Rolling in the Deep”?', 'options' => ['Beyoncé', 'Adele', 'Rihanna', 'Taylor Swift'], 'correct' => 'Adele'],
            ['question' => 'Bob Marley was famous for which music genre?', 'options' => ['Rock', 'Reggae', 'Jazz', 'Hip Hop'], 'correct' => 'Reggae'],

            // Military
            ['question' => 'In which year did World War II end?', 'options' => ['1943', '1944', '1945', '1946'], 'correct' => '1945'],
            ['question' => 'Which country has the largest standing army today?', 'options' => ['USA', 'Russia', 'India', 'China'], 'correct' => 'China'],
            ['question' => 'What does NATO stand for?', 'options' => ['North Atlantic Treaty Organization', 'National Army Training Organization', 'Naval and Tactical Operations', 'None'], 'correct' => 'North Atlantic Treaty Organization'],
            ['question' => 'Which general led the Allied forces on D-Day?', 'options' => ['Patton', 'Eisenhower', 'Montgomery', 'MacArthur'], 'correct' => 'Eisenhower'],
            ['question' => 'Which war is also known as “The Great War”?', 'options' => ['World War I', 'World War II', 'Korean War', 'Vietnam War'], 'correct' => 'World War I'],

            // Agriculture
            ['question' => 'Which crop is the staple food for more than half the world’s population?', 'options' => ['Rice', 'Wheat', 'Maize', 'Potato'], 'correct' => 'Rice'],
            ['question' => 'Nigeria is the world’s largest producer of what crop?', 'options' => ['Cocoa', 'Cassava', 'Yam', 'Palm Oil'], 'correct' => 'Cassava'],
            ['question' => 'Which farming method uses water instead of soil?', 'options' => ['Hydroponics', 'Crop Rotation', 'Irrigation', 'Mixed Farming'], 'correct' => 'Hydroponics'],
            ['question' => 'What is the primary product of dairy farming?', 'options' => ['Cheese', 'Milk', 'Butter', 'Eggs'], 'correct' => 'Milk'],
            ['question' => 'What type of crop is groundnut?', 'options' => ['Cereal', 'Legume', 'Tuber', 'Fruit'], 'correct' => 'Legume'],

            // General Knowledge
            ['question' => 'What is the capital of Canada?', 'options' => ['Toronto', 'Vancouver', 'Ottawa', 'Montreal'], 'correct' => 'Ottawa'],
            ['question' => 'Which planet is closest to the sun?', 'options' => ['Venus', 'Mercury', 'Mars', 'Earth'], 'correct' => 'Mercury'],
            ['question' => 'What is the largest mammal in the world?', 'options' => ['Elephant', 'Blue Whale', 'Giraffe', 'Shark'], 'correct' => 'Blue Whale'],
            ['question' => 'How many continents are there?', 'options' => ['5', '6', '7', '8'], 'correct' => '7'],
            ['question' => 'What is the chemical symbol for water?', 'options' => ['H2O', 'O2', 'CO2', 'HO2'], 'correct' => 'H2O'],
        ];

        foreach ($questions as $q) {
            Question::updateOrCreate([
                'question' => $q['question'],
                'correct' => $q['correct']
            ], [
                'options' => $q['options']
            ]);
        }
    }
}
