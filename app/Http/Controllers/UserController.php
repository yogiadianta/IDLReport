<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    //

    public function __construct(){

        // $user = Auth::user();
        // dump(Auth::user());
        // $user->test = "testing";
        // dump($user);

        // $this->userAuth();
    }

    function userAuth(){
        $master = DB::select('select * from master 
                            join users on master.user_id = users.id
                            join level on master.level_id = level.id
                            join location on master.location_id = location.id 
                            join area on location.area_id = area.id 
                            join task on master.task_id = task.id 
                            where user_id = ? ', [Auth::user()->id] );
        Auth::user()->master = $master;

        $activity = DB::select('select * from user_activity 
                                join users on user_activity.user_id = users.id
                                join activity on user_activity.activity_id = activity.id
                                where user_id = ?', [Auth::user()->id]);

        Auth::user()->activity = $activity;

        $location = DB::select('select DISTINCT user_id, location_id, location_name from master 
                                join location on master.location_id = location.id
                                where user_id = ?', [Auth::user()->id]);
        Auth::user()->location = $location;

        Auth::user()->locationnow = $location[0]->location_id;

        Auth::user()->level = $master[0]->level_id;

        // dump(Auth::user());
        // die();
    }

    public function addUser(){
        $this->userAuth();

        $level = DB::select('select * from level');
        dump($level);
        $location = DB::select('select * from location 
                                join area on location.area_id = area.id');
        $task = DB::select('select * from task ');

        // dump($level);
        
        return view('User.addUser')->with('level', $level)->with('location', $location)->with('task', $task);
    }

    public function saveUser(Request $request){
        $this->userAuth();

        // return view('User.addUser');

        User::create([
            'name' => $request['username'],
            'email' => $request['email'],
            'level' => $request['level'],
            'password' => Hash::make($request['password']),
        ]);

        $user = DB::select('select * from users where email = ?', [$request['email']]);

        $user_id = $user[0]->id;
        $user_id = strval($user_id);
        $asd=1;
        foreach($request->task as $item){
            // dump($user[0]->id);
            DB::insert('insert into master (user_id, level_id, location_id, task_id) values(?, ?, ?, ?)', [
                $user_id, $request->level, $request->location_id,  $item
            ]);
        }
        return redirect('/listUser/'.Auth::user()->locationnow);
    }


    public function listUser(){
        $this->userAuth();

        $users = DB::select('select * from users');

        // die();

        return view('User.listUser')->with('users', $users);
    }

    public function listManageUser(){
        $this->userAuth();

        // $manageUsers = DB::select('select * from master
        //                             join users on master.user_id = users.id
        //                             join location on master.location_id = location.id
        //                             join task on master.task_id = task.id
        //                             join activity on master.activity_id = activity.id
        //                             ORDER BY master.user_id');
        $manageUsers = DB::select('select * from master
                                    join users on master.user_id = users.id
                                    join location on master.location_id = location.id
                                    join task on master.task_id = task.id
                                    ORDER BY master.user_id');

        // dump($manageUsers);

        return view('User.listManageUser')->with('manageUsers', $manageUsers);
    }

    public function manageUser(){
        $this->userAuth();

        $user = Auth::user();

        $user->userlist = array();
        $user->usernamelist = array();
        $user->levellist = array();
        $user->locationlist = array();
        $user->locationname = array();
        $user->tasklist = array();
        $user->activitylist = array();

        // $master = DB::select('select * from master
        //                     join location on master.location_id = location.id
        //                     join area on location.area_id = area.id
        //                     join task on master.task_id = task.id
        //                     join activity on master.activity_id = activity.id
        //                     where user_id = ? ', [$user->id], );

        $master = DB::select('select * from master
                                join users on master.user_id = users.id
                                join location on master.location_id = location.id
                                ');
        $users = DB::select('select * from users');
        $level = DB::select('select* from level');
        $location = DB::select('select * from location');
        $task = DB::select('select * from task');
        $activity = DB::select('select * from activity');

        $user->usermaster = $users;
        $user->levelmaster = $level;
        $user->locationmaster = $location;
        $user->taskmaster = $task;
        $user->activitymaster = $activity;

        // dump($users);
        // dump($location);
        // dump($task);
        // dump($activity);

        foreach($users as $i => $item){
            $user->usernamelist = array_merge($user->usernamelist, [$item->name]);
        }
        foreach($level as $i => $item){
            $user->levellist = array_merge($user->levellist, [$item->level_name]);
        }
        foreach($location as $i => $item){
            $user->locationlist = array_merge($user->locationlist, [$item->location_name]);
        }
        foreach($task as $i => $item){
            $user->tasklist = array_merge($user->tasklist, [$item->task_name]);
        }
        foreach($activity as $i => $item){
            $user->activitylist = array_merge($user->activitylist, [$item->activity_name]);
        }

        // $user->master = $master;
        // foreach($master as $i => $item){
        //     $user->userlist = array_unique(array_merge($user->userlist, [$item->user_id]));
        //     $user->username = array_unique(array_merge($user->username, [$item->name]));
        //     $user->locationlist = array_unique(array_merge($user->locationlist, [$item->location_id]));
        //     $user->locationname = array_unique(array_merge($user->locationname, [$item->location_name]));

        //     $user->tasklist = array_unique(array_merge($user->tasklist, [$item->task_id]));
        //     $user->activitylist = array_unique(array_merge($user->activitylist, [$item->activity_id]));
        // }

        // dump($user);

        return view('User.manageUser')->with('user', $user);
    }

    public function saveManageUser(Request $request){
        $this->userAuth();

        // return view('User.manageUser');
        // return 'save manage user';
        // $asd=1;
        // dump($request->user_id);
        DB::insert('insert into master (user_id, level_id, location_id, task_id, activity_id) values(?, ?, ?, ?,?)', [
            $request->user_id,$asd, $request->location_id,  $request->task_id, $request->level_access_id
        ]);

        return redirect('/listUser');
    }
}
