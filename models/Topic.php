<?php namespace Klubitus\Forum\Models;

use Auth;
use Cms\Classes\Controller;
use Db;
use Model;
use October\Rain\Database\QueryBuilder;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;
use Str;

/**
 * Topic Model
 */
class Topic extends Model {
    use Validation;

    /**
     * @var  string  The database table used by the model.
     */
    public $table = 'forum_topics';

    /**
     * @var  array  Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var  array  Fillable fields
     */
    protected $fillable = ['name'];

    /**
     * @var  array  The attributes that should be visible in arrays.
     */
    protected $visible = ['id', 'name', 'area', 'created_at', 'updated_at'];

    /**
     * @var  array  Date fields
     */
    public $dates = ['last_post_at'];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [
        'area'   => 'Klubitus\Forum\Models\Area',
        'author' => 'RainLab\User\Models\User',
    ];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public $rules = [
        'name'          => 'required',
        'forum_area_id' => 'required',
    ];


    /**
     * Create a topic and post in an area.
     *
     * @param  Area   $area
     * @param  User   $user
     * @param  array  $data
     * @return  Topic
     */
    public static function createInArea(Area $area, User $user, array $data) {
        $topic = new static;
        $topic->name = array_get($data, 'name');
        $topic->area = $area;
        $topic->author = $user;

        Db::transaction(function() use ($topic) {
            $topic->save();
        });

        return $topic;
    }


    public function afterCreate() {
        $this->channel->increment('topic_count');
    }


    public function afterDelete() {
        $this->channel->decrement('topic_count');
    }


    public function canPost($user = null) {
        if (!$user) {
            $user = Auth::getUser();
        }

        if (!$user) {
            return false;
        }

        if ($this->is_locked) {
            return false;
        }

        return true;
    }


    public function increaseReadCount() {
        $this->timestamps = false;
        $this->increment('read_count');
        $this->timestamps = true;
    }


    /**
     * Filter areas.
     *
     * @param   QueryBuilder  $query
     * @param   int|array     $areas
     * @return  QueryBuilder
     */
    public function scopeAreas($query, $areas) {
        if (!is_array($areas)) {
            $areas = [$areas];
        }

        return $query->whereIn('forum_area_id', $areas);
    }


    /**
     * Get topics with latest replies.
     *
     * @param   QueryBuilder  $query
     * @return  QueryBuilder
     */
    public function scopeRecentPosts($query) {
        return $query->orderBy('last_post_at', 'desc');
    }


    /**
     * Get latest topics.
     *
     * @param   QueryBuilder  $query
     * @return  QueryBuilder
     */
    public function scopeRecentTopics($query) {
        return $query->orderBy('created_at', 'desc');
    }


    /**
     * Search topics and posts.
     *
     * @param   QueryBuilder  $query
     * @param   string        $search
     * @return  QueryBuilder
     */
    public function scopeSearch($query, $search) {
        $search = trim($search);

        if (strlen($search)) {
            $query->where(function($query) use ($search) {
//                $query->whereHas('posts', function($query) use ($search) {
//                    $query->searchWhere($search, 'post');
//                });

                $query->orSearchWhere($search, 'name');
            });
        }

        return $query;
    }


    /**
     * Set current object url.
     *
     * @param  string      $pageName
     * @param  Controller  $controller
     * @return  string
     */
    public function setUrl($pageName, Controller $controller) {
        $params = [
            'topic_id' => $this->id . '-' . Str::slug($this->name)
        ];

        return $this->url = $controller->pageUrl($pageName, $params);
    }

}
