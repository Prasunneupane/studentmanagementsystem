<?php

namespace App\Faker;

use Faker\Provider\Base;

class NepaliProvider extends Base
{
    protected static $nepaliSurnames = [
        'Adhikari', 'Acharya', 'Aryal', 'Bajracharya', 'Baniya',
        'Basnet', 'Bhandari', 'Bhattarai', 'Bista', 'Budhathoki',
        'Chaudhary', 'Chhetri', 'Dahal', 'Dhakal', 'Dongol',
        'Gautam', 'Ghimire', 'Gurung', 'Hamal', 'Kafle',
        'Karki', 'Khadka', 'Koirala', 'Lama', 'Magar',
        'Maharjan', 'Malla', 'Neupane', 'Ojha', 'Pangeni',
        'Pandey', 'Parajuli', 'Pathak', 'Poudel', 'Rai',
        'Rana', 'Regmi', 'Rijal', 'Shahi', 'Sharma',
        'Sherpa', 'Shrestha', 'Sigdel', 'Subedi', 'Thakuri',
        'Thapa', 'Tiwari', 'Tamang', 'Yadav', 'KC'
    ];

    protected static $nepaliFirstNames = [
        'Sitaram', 'Gita', 'Hari', 'Sita', 'Ram', 'Shova',
        'Bishnu', 'Laxmi', 'Krishna', 'Radha', 'Manish', 'Sunita',
        'Rajesh', 'Sarita', 'Bikash', 'Rita', 'Prakash', 'Mina',
        'Suresh', 'Nirmala', 'Dipak', 'Sushila', 'Mahesh', 'Goma',
        'Aayush', 'Aarav', 'Aarohi', 'Aashish', 'Aayusha',
        'Anish', 'Anisha', 'Apeksha', 'Asmita', 'Ayushma',
        'Bibek', 'Bipin', 'Bishal', 'Bimala', 'Binita',
        'Chandan', 'Chirag', 'Chhiring', 'Chahana',
        'Deepa', 'Deepesh', 'Dikshya', 'Dinesh', 'Diya',
        'Elina', 'Erina', 'Eshan',
        'Gaurav', 'Ganga', 'Gitanjali',
        'Ishwor', 'Ishika', 'Ishan',
        'Jenisha', 'Jenish', 'Juna', 'Junisha',
        'Kamal', 'Kanchan', 'Kiran', 'Kritika', 'Kushal',
        'Luna', 'Laxman', 'Lokesh',
        'Manita', 'Manoj', 'Milan', 'Muna', 'Muskan',
        'Nabin', 'Namrata', 'Nikita', 'Nischal',
        'Prabin', 'Pratima', 'Prisha', 'Prayas',
        'Rabin', 'Rachana', 'Riya', 'Ritesh',
        'Sagun', 'Samir', 'Sanjog', 'Saroj', 'Sujan',
        'Tenzin', 'Tshering',
        'Ujjwal', 'Usha',
        'Yogesh', 'Yunika', 'Yubaraj',
    ];

    protected static $nepaliMiddleNames = [
        'Bahadur', 'Kumar', 'Prasad', 'Lal', 'Maya',
        'Devi', 'Raj', 'Man', 'Bir', 'Chandra',
        'Narayan', 'Hari', 'Krishna', 'Shankar',
        'Indra', 'Gopal', 'Surya', 'Prem'
    ];

    protected static $addresses = [
        'Kathmandu', 'Pokhara', 'Chitwan', 'Biratnagar', 'Lalitpur',
        'Bhaktapur', 'Butwal', 'Janakpur', 'Hetauda', 'Dharan',
        'Nepalgunj', 'Birendranagar', 'Kailali', 'Kanchanpur',
        'Gadhawa', 'Ghorahi', 'Lamahi', 'Deukhuri', 'Buddi'
    ];

    protected static $relations = [
        'father', 'mother', 'uncle', 'auntie', 'brother',
        'sister', 'nephew', 'niece', 'grandfather', 'grandmother',
        'cousin', 'guardian', 'stepfather', 'stepmother'
    ];

    // ✅ First Name
    public function nepaliFirstName(): string
    {
        return $this->generator->randomElement(static::$nepaliFirstNames);
    }

    // ✅ Middle Name (optional)
    public function nepaliMiddleName(): ?string
    {
        return $this->generator->optional(0.4) // 40% chance
            ->randomElement(static::$nepaliMiddleNames);
    }

    // ✅ Last Name
    public function nepaliSurname(): string
    {
        return $this->generator->randomElement(static::$nepaliSurnames);
    }

    // ✅ Full Name (with optional middle)
    public function nepaliFullName(): string
    {
        $first = $this->nepaliFirstName();
        $middle = $this->nepaliMiddleName();
        $last = $this->nepaliSurname();

        return trim($first . ' ' . ($middle ? $middle . ' ' : '') . $last);
    }

    // ✅ Address
    public function nepaliAddress(): string
    {
        return $this->generator->randomElement(static::$addresses);
    }

    // ✅ Guardian relation
    public function guardianRelation(): string
    {
        return $this->generator->randomElement(static::$relations);
    }
}