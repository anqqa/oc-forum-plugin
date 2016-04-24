<?php namespace Klubitus\Forum\Models;

use Auth;
use Cms\Classes\Controller;
use Model;
use October\Rain\Database\QueryBuilder;
use October\Rain\Database\Traits\NestedTree;
use October\Rain\Database\Traits\Validation;
use Str;

/**
 * Area Model
 */
class Area extends Model {
    use NestedTree;
    use Validation;

    public $implement = ['@Rainlab.Translate.Behaviors.TranslatableModel'];
    public $translatable = ['name', 'description'];

    /**
     * @var  string  The database table used by the model.
     */
    public $table = 'forum_areas';

    /**
     * @var  array  Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var  array  Fillable fields
     */
    protected $fillable = ['name', 'description', 'parent_id', 'is_hidden', 'is_private', 'is_moderated'];

    /**
     * @var  array  The attributes that should be visible in arrays.
     */
    protected $visible = ['name', 'description'];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    /**
     * @var  array  Validation rules
     */
    public $rules = [
        'name' => 'required',
    ];


    /**
     * Get accessible areas' ids.
     * 
     * @param  bool  $isAuthenticated
     * @return  array
     */
    public static function getAccessibleIds($isAuthenticated = null) {
        static $ids = [];
        
        is_null($isAuthenticated) and $isAuthenticated = Auth::check();
        
        if ($isAuthenticated) {
            if (isset($ids['authenticated'])) {
                return $ids['authenticated'];
            }
            
            return $ids['authenticated'] = self::isVisible()->lists('id');
        }
        else {
            if (isset($ids['unauthenticated'])) {
                return $ids['unauthenticated'];
            }
            
            return $ids['unauthenticated'] = self::isVisible()->isPublic()->lists('id');
        }
    }
    
    
    /**
     * Filter public areas.
     *
     * @param   QueryBuilder  $query
     * @return  QueryBuilder
     */
    public function scopeIsPublic($query) {
        return $query->where('is_private', '<>', true);
    }


    /**
     * Filter visible areas.
     *
     * @param   QueryBuilder  $query
     * @return  QueryBuilder
     */
    public function scopeIsVisible($query) {
        return $query->where('is_hidden', '<>', true);
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
            'area_id' => $this->id . '-' . Str::slug($this->name)
        ];

        return $this->url = $controller->pageUrl($pageName, $params);
    }

}
