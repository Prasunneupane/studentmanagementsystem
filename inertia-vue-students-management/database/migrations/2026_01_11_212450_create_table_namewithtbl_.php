<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         if (Schema::hasTable('municipalities')) {
            Schema::drop('municipalities');
        }

        if (Schema::hasTable('districts')) {
            Schema::drop('districts');
        }

        if (Schema::hasTable('states')) {
            Schema::drop('states');
        }
        Schema::create('tbl_states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nepali_name')->nullable(); // Assuming a field for the Nepali name
            $table->timestamps(); 
        });
        
         DB::table('tbl_states')->insert([
            ['name' => 'Koshi Pradesh', 'nepali_name' => 'कोशी प्रदेश', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Madhesh Pradesh', 'nepali_name' => 'मधेश प्रदेश', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bagmati Pradesh', 'nepali_name' => 'बागमती प्रदेश', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gandaki Pradesh', 'nepali_name' => 'गण्डकी प्रदेश', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lumbini Pradesh', 'nepali_name' => 'लुम्बिनी प्रदेश', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Karnali Pradesh', 'nepali_name' => 'कर्णाली प्रदेश', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sudurpashchim Pradesh', 'nepali_name' => 'सुदूरपश्चिम प्रदेश', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('tbl_districts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('state_id')->nullable();
            $table->timestamps(); 
        });

        DB::table('tbl_districts')->insert([
            ['name'=>'Panchthar','state_id'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Ilam','state_id'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Jhapa','state_id'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Morang','state_id'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Sunasari','state_id'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Dhankuta','state_id'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Terhathum','state_id'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Sankhuwasabha','state_id'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Bhojpur','state_id'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Solukhumbu','state_id'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Okhaldhunga','state_id'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Khotang','state_id'=>1,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Udayapur','state_id'=>1,'created_at'=>now(),'updated_at'=>now()],

            ['name'=>'Saptari','state_id'=>2,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Siraha','state_id'=>2,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Dhanusha','state_id'=>2,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Mahottari','state_id'=>2,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Sarlahi','state_id'=>2,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Rautahat','state_id'=>2,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Bara','state_id'=>2,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Parsa','state_id'=>2,'created_at'=>now(),'updated_at'=>now()],

            ['name'=>'Sindhuli','state_id'=>3,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Ramechhap','state_id'=>3,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Dolakha','state_id'=>3,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Sindhupalchok','state_id'=>3,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Kavrepalanchok','state_id'=>3,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Lalitpur','state_id'=>3,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Bhaktapur','state_id'=>3,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Kathmandu','state_id'=>3,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Nuwakot','state_id'=>3,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Rasuwa','state_id'=>3,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Dhading','state_id'=>3,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Makawanpur','state_id'=>3,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Chitwan','state_id'=>3,'created_at'=>now(),'updated_at'=>now()],

            ['name'=>'Gorkha','state_id'=>4,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Lamjung','state_id'=>4,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Tanahun','state_id'=>4,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Syangja','state_id'=>4,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Kaski','state_id'=>4,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Manang','state_id'=>4,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Mustang','state_id'=>4,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Myagdi','state_id'=>4,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Parbat','state_id'=>4,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Baglung','state_id'=>4,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Nawalparasi','state_id'=>4,'created_at'=>now(),'updated_at'=>now()],

            ['name'=>'Gulmi','state_id'=>5,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Palpa','state_id'=>5,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Rupandehi','state_id'=>5,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Kapilbastu','state_id'=>5,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Arghakhanchi','state_id'=>5,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Pyuthan','state_id'=>5,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Rolpa','state_id'=>5,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Rukum','state_id'=>5,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Dang','state_id'=>5,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Banke','state_id'=>5,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Bardiya','state_id'=>5,'created_at'=>now(),'updated_at'=>now()],

            ['name'=>'Salyan','state_id'=>6,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Surkhet','state_id'=>6,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Dailekh','state_id'=>6,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Jajarkot','state_id'=>6,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Dolpa','state_id'=>6,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Jumla','state_id'=>6,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Kalikot','state_id'=>6,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Mugu','state_id'=>6,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Humla','state_id'=>6,'created_at'=>now(),'updated_at'=>now()],

            ['name'=>'Bajura','state_id'=>7,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Bajhang','state_id'=>7,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Achham','state_id'=>7,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Doti','state_id'=>7,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Kailali','state_id'=>7,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Kanchanpur','state_id'=>7,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Dadeldhura','state_id'=>7,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Baitadi','state_id'=>7,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Darchula','state_id'=>7,'created_at'=>now(),'updated_at'=>now()],
        ]);

         Schema::create('tbl_municipalities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('district_id')->nullable(); // Assuming a foreign key to districts
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_municipalities');
        Schema::dropIfExists('tbl_districts');
        Schema::dropIfExists('tbl_states');
    }
};
