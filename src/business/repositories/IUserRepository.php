<?php

interface IUserRepository
{
	function getAllUsers();
	function registerUser($userName, $email, $firstName, $lastName, $password);
}
