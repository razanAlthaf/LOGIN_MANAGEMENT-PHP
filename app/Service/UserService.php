<?php

namespace Razan\Service;

use Razan\Model\UserRegisterRequest;
use Razan\Model\UserRegisterResponse;
use Razan\Repository\UserRepository;
use Razan\Exception\ValidationException;
use Razan\Domain\User;
use Razan\Config\Database;
use Razan\Model\UserLoginRequest;
use Razan\Model\UserLoginResponse;
use Razan\Model\UserUpdateProfileRequest;
use Razan\Model\UserUpdateProfileResponse;
use Razan\Model\UserUpdatePasswordRequest;
use Razan\Model\UserUpdatePasswordResponse;


class UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(UserRegisterRequest $request) : UserRegisterResponse
    {
        $this->validateUserRegistrationRequest($request);

        try {
            Database::beginTransaction();
            $user = $this->userRepository->findById($request->id);
            if($user != null){
                throw new ValidationException("User Id Already Exist");
            }
    
            $user = new User();
            $user->id = $request->id;
            $user->name = $request->name;
            $user->password = password_hash($request->password, PASSWORD_BCRYPT);
    
            $this->userRepository->save($user);
    
            $response = new UserRegisterResponse();
            $response->user = $user;
            Database::commitTransaction();
            return $response;
            } catch (\Exception $exception) {
                Database::rollbackTransaction();
                throw $exception;
        }
    }

    private function validateUserRegistrationRequest(UserRegisterRequest $request){
        if($request->id == null || $request->name == null || $request->password == null ||
        trim($request->id) == "" || trim($request->name) == "" || trim($request->password) == ""){
            throw new ValidationException("Id, Name, Password Can't Blank");
        }
    }

    public function login(UserLoginRequest $request) : UserLoginResponse
    {
        $this->validateUserLoginRequest($request);

        $user = $this->userRepository->findById($request->id);
        if($user == null){
            throw new ValidationException("Id or password is wrong");
        }

        if(password_verify($request->password, $user->password)){
            $response = new UserLoginResponse();
            $response->user = $user;
            return $response;
        }else {
            throw new ValidationException("Id or password is wrong");
        }
    }

    private function validateUserLoginRequest(UserLoginRequest $request){
        if($request->id == null || $request->password == null ||
        trim($request->id) == "" || trim($request->password) == ""){
            throw new ValidationException("Id, Password Can't Blank");
        }
    }

    public function updateProfile(UserUpdateProfileRequest $request): UserUpdateProfileResponse
    {
        $this->validateUserUpdateProfileRequest($request);
        

        try {
            Database::beginTransaction();
            $user = $this->userRepository->findById($request->id);
            if($user == null){
                throw new ValidationException("User Not Found");
            }
    
            $user->name = $request->name;
            $this->userRepository->update($user);
    
            Database::commitTransaction();

            $response = new UserUpdateProfileResponse();
            $response->user = $user;
            return $response;
            } catch (\Exception $exception) {
                Database::rollbackTransaction();
                throw $exception;
        }
    }

    private function validateUserUpdateProfileRequest(UserUpdateProfileRequest $request){
        if($request->id == null || $request->name == null ||
        trim($request->id) == "" || trim($request->name) == ""){
            throw new ValidationException("Id, Name Can't Blank");
        }
    }

    public function updatePassword(UserUpdatePasswordRequest $request) : UserUpdatePasswordResponse
    {
        $this->validateUserUpdatePasswordRequest($request);

        try {
            Database::beginTransaction();
            $user = $this->userRepository->findById($request->id);
            if($user == null){
                throw new ValidationException("User Not Found");
            }
    
            if(!password_verify($request->oldPassword, $user->password)){
                throw new ValidationException("Old Password is Wrong");
            }
    
            $user->password = password_hash($request->newPassword, PASSWORD_BCRYPT);
            $this->userRepository->update($user);
    
            Database::commitTransaction();

            $response = new UserUpdatePasswordResponse();
            $response->user = $user;
            return $response;
            } catch (\Exception $exception) {
                Database::rollbackTransaction();
                throw $exception;
        }
    }

    private function validateUserUpdatePasswordRequest(UserUpdatePasswordRequest $request){
        if($request->id == null || $request->oldPassword == null || $request->newPassword == null ||
        trim($request->id) == "" || trim($request->oldPassword) == "" || trim($request->newPassword) == ""){
            throw new ValidationException("Id, Old Password, New Password Can't Blank");
        }
    }
}