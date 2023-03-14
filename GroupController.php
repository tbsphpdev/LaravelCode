<?php

namespace App\Http\Controllers;

use App\User;
use App\Group;
use App\GroupUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Validator;
use Image;
use Session;
use File;
use DB;
use URL;
use Yajra\DataTables\Facades\DataTables;
use App\Helper\GlobalHelper;
use Auth;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::all();
        return view('admin.groups.groups',['groups'=> $groups]);
    }

    public function ajaxGroups()
    {
       return DataTables::eloquent(Group::select('*')
            ->Join('group_users', 'groups.group_id', '=', 'group_users.group_id')
            ->where('group_users.user_id',Auth::User()->id)
            ->select('groups.group_id','groups.user_id','groups.group_name','groups.group_description'))
            ->addColumn('action', function ($groups) {
               return '<a class="label label-success" title="Group Chat" href="'.route('adminGroupChat', [$groups->group_id]).'"><i class="fa fa-comments-o" aria-hidden="true"></i>&nbsp</a>
                        <a class="label label-primary" href="' . url('admin/groups/view', ['id' => $groups->group_id]) . '"  title="View / Update"><i class="fa fa-eye"></i>&nbsp</a>
                       <a class="label label-danger" href="javascript:;"  title="Delete" onclick="deleteConfirm('.$groups->group_id.')"><i class="fa fa-trash"></i>&nbsp</a>
                       ';
            })
            ->editColumn('user_id', function($groups) {
               return GlobalHelper::getUserById($groups->user_id)->first_name.' '.GlobalHelper::getUserById($groups->user_id)->last_name;
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    public function addNewGroup(){
        $users = User::where('id','!=','1')->get();
        return view('admin.groups.groupsAdd',compact("users"));
    }

    public function store(Request $request)
    {
        $rules = array(
            'group_name' => 'required',
            'group_description' => 'required',
            'user_id' => 'required'
        );
        $messages = [
        ];

        $validator = Validator::make(Input::all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()
                            ->withErrors($validator)
                            ->withInput();
        } else {
            $group = new Group();
            $group->group_name = $request['group_name'];
            $group->group_description = $request['group_description'];
            $group->user_id = Auth::User()->id;
            if ($group->save()) {
                $groupMember = new GroupUser();
                $groupMember->group_id = $group->group_id;
                $groupMember->user_id = Auth::User()->id;
                $groupMember->group_created_by = Auth::User()->id;
                $groupMember->save();

                $members = $request['user_id'];
                foreach($members as $member) {
                  $groupMembers = new GroupUser();
                  $groupMembers->group_id = $group->group_id;
                  $groupMembers->user_id = $member;
                  $groupMembers->group_created_by = Auth::User()->id;
                  $groupMembers->save();
                }

                Session::flash('message', 'Group Added Succesfully !');
                Session::flash('alert-class', 'success');
                return redirect('admin/groups');

            } else {
                Session::flash('message', 'Oops !! Something went wrong!');
                Session::flash('alert-class', 'error');
                return redirect('admin/groups');
            }
        }
    }

    public function update(Request $request)
    {

        $rules = array(
          'group_name' => 'required',
          'group_description' => 'required',
          'user_id' => 'required'
        );
        $messages = [
        ];

        $validator = Validator::make(Input::all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()
                            ->withErrors($validator)
                            ->withInput();
        } else {

            $group = Group::find($request->group_id);
            $group->group_name = $request['group_name'];
            $group->group_description = $request['group_description'];
            $group->updated_at = date("Y-m-d H:i:s");

            if ($group->save()) {
                $members = $request['user_id'];
                $findUsers = GroupUser::where('group_id',$group->group_id)->pluck('user_id')->toArray();
                $addmembers = array_diff($members,$findUsers);
                $deletemembers = array_diff($findUsers,$members);
                if(!empty($addmembers)) {
                  foreach ($addmembers as $member) {
                    $groupMembers = new GroupUser();
                    $groupMembers->group_id = $group->group_id;
                    $groupMembers->user_id = $member;
                    $groupMembers->group_created_by = Auth::User()->id;
                    $groupMembers->save();
                  }
                } elseif (!empty($deletemembers)) {
                    foreach ($deletemembers as $member) {
                      GroupUser::where('user_id',$member)->where('group_id',$group->group_id)->delete();
                    }
                }

                Session::flash('message', 'Group Updated Succesfully !');
                Session::flash('alert-class', 'success');

                return redirect('admin/groups');
            } else {
                Session::flash('message', 'Oops !! Something went wrong!');
                Session::flash('alert-class', 'error');
                return redirect('admin/groups');
            }
        }
    }

    public function view($id)
    {
        $group = Group::find($id);
        $groupMember = GroupUser::where('group_id',$id)->pluck('user_id')->toArray();
        if(!empty($group)){
            $users = User::get();
            return view('admin.groups.groupsView',['users' => $users,"group"=>$group, "groupMember"=>$groupMember]);
        }
        else{
            abort(404);
        }
    }

    public function destroy($id)
    {
        $dealerGroup = Group::find($id);
        if ($dealerGroup->delete()) {
            Session::flash('message', 'Group Deleted !!');
            Session::flash('alert-class', 'warning');
            return redirect('admin/groups');
        }
    }
}
