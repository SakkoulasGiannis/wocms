<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'title', 'slug', 'html', 'html_css', 'att'
    ];

    protected $casts = [
        'att' => 'array',
    ];
    /**
     * Add item to att
     */
    public function addAtt($item): void
    {
        $items = $this->att ?? [];
        $items[] = $item;
        $this->att = $items;
        $this->save();
    }

    /**
     * Remove item from att by index
     */
    public function removeAtt($index): void
    {
        $items = $this->att ?? [];
        if (isset($items[$index])) {
            unset($items[$index]);
            $this->att = array_values($items);
            $this->save();
        }
    }

    /**
     * Get count of items in att
     */
    public function getAttCount(): int
    {
        return is_array($this->att) ? count($this->att) : 0;
    }
}