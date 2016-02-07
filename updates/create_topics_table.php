<?php namespace Klubitus\Forum\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateTopicsTable extends Migration
{

    public function up() {
        Schema::create('forum_topics', function($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->timestamps();

            $table->integer('forum_area_id');

            $table->string('name');
            $table->integer('author_id')->nullable();
            $table->string('author_name')->nullable();
            $table->integer('first_post_id')->nullable();
            $table->integer('last_post_id')->nullable();
            $table->string('last_poster')->nullable();
            $table->timestamp('last_post_at')->nullable();
            $table->integer('post_count')->default(0);
            $table->integer('read_count')->default(0);
            $table->boolean('is_locked')->default(0);
            $table->boolean('is_sticky')->default(0);
            $table->boolean('is_sinking')->default(0);

            $table->foreign('forum_area_id')->references('id')->on('forum_areas');
            $table->foreign('author_id')->references('id')->on('users');
            $table->index(['is_sticky', 'last_post_at'], 'sticky_post_time');
        });
    }

    public function down() {
//        Schema::dropIfExists('forum_topics');
    }

}
