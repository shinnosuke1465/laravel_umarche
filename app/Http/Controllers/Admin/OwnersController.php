<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Owner; // Eloquent エロクアント
use App\Models\Shop;
use Illuminate\Support\Facades\DB; // QueryBuilder クエリビルダ
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Throwable;
use Illuminate\Support\Facades\Log;

class OwnersController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $owners = Owner::select('id', 'name', 'email', 'created_at')
            ->paginate(3);

        return view(
            'admin.owners.index',
            compact('owners')
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.owners.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:owners',
            'password' => 'required|string|confirmed|min:8',
        ]);

        try{
            DB::transaction(function () use($request) {
                //ownerテーブルに入力された値を挿入
                $owner = Owner::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);

                Shop::create([
                    'owner_id' => $owner->id,
                    'name' => '店名を入力してください',
                    'information' => '',
                    'filename' => '',
                    'is_selling' => true
                ]);
            }, 2);
        }catch(Throwable $e){
            Log::error($e);
            throw $e;
        }

        //ユーザ登録が成功したらユーザー一覧画面へリダイレクト
        return redirect()->route('admin.owners.index')->with(['message' => 'オーナー登録を実施しました。','status' => 'info']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $owner = Owner::findOrFail($id);
        // dd($owner);
        return view('admin.owners.edit', compact('owner'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $owner = Owner::findOrFail($id);
        $owner->name = $request->name;
        $owner->email = $request->email;
        $owner->password = Hash::make($request->password);
        $owner->save();

        return redirect()
        ->route('admin.owners.index')
        ->with(['message' => 'オーナー情報を更新しました。',
        'status' => 'info']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Owner::findOrFail($id)->delete(); //ソフトデリート

        return redirect()
        ->route('admin.owners.index')
        ->with(['message' => 'オーナー情報を削除しました。',
        'status' => 'alert']);
    }

    public function expiredOwnerIndex(){
        $expiredOwners = Owner::onlyTrashed()->get();
        return view('admin.expired-owners', compact('expiredOwners'));
    }
    
    public function expiredOwnerDestroy($id){
        Owner::onlyTrashed()->findOrFail($id)->forceDelete();
        return redirect()->route('admin.expired-owners.index'); 
    }
}
