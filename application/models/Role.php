<?php

class Role
{
    const GUEST = 'guest';
    const ADMIN = 'admin';

    public function get()
    {
        return Role::GUEST;
    }
}
