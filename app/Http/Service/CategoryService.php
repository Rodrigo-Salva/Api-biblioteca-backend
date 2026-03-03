<?php

namespace App\Http\Service;

use App\Models\Category;

class CategoryService
{
    public function list($all = false)
    {
        return $all ? Category::all() : Category::paginate(10);
    }

    public function create(array $data)
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data)
    {
        $category->update($data);
        return $category;
    }

    public function delete(Category $category)
    {
        $category->delete();
    }
}
