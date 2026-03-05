<?php

namespace App\Http\Service;

use App\Models\User;

class UserService
{
    public function list($all = false)
    {
        return $all ? User::all() : User::paginate(10);
    }

    public function find(int $id)
    {
        return User::findOrFail($id);
    }

    public function update(int $id, array $data)
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user;
    }

    public function delete(int $id)
    {
        User::destroy($id);
    }
}
