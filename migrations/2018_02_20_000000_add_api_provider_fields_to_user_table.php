<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApiProviderFieldsToUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      /*
      //not sure why we need this as a migration in *every* single project.......
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn($table->getTable(), 'uuid')) {
                $table->char('uuid', 36)->unique()->default('');
            }
            if (!Schema::hasColumn($table->getTable(), 'username')) {
                $table->string('username')->unique()->default('');
            }
            if (!Schema::hasColumn($table->getTable(), 'privileges')) {
                $table->text('privileges')->nullable();
            }
            if (!Schema::hasColumn($table->getTable(), 'apitoken')) {
                $table->string('apitoken', 16)->unique()->nullable();
            }
            if (!Schema::hasColumn($table->getTable(), 'apisecretkey')) {
                $table->string('apisecretkey', 40)->nullable();
            }
        });
      */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
