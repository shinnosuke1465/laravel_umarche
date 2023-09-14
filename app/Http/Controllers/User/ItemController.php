<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Stock;
use App\Models\PrimaryCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;
use App\Jobs\SendThanksMail;

class ItemController extends Controller
{
    public function __construct()
    {
        //「ユーザー」が認証されているかを確認
        $this->middleware('auth:users');

        $this->middleware(function ($request, $next) {
            //ItemのIDを取得
            $id = $request->route()->parameter('item');
            if (!is_null($id)) {

                $itemId = Product::availableItems()->where('products.id', $id)->exists();
                if (!$itemId) {
                    abort(404);
                }
            }
            return $next($request);
        });
    }
    public function index()
    {
        //DB:row...sql文を直接かける
        $stocks = DB::table('t_stocks')
            ->select(
                'product_id',
                DB::raw('sum(quantity) as quantity')
            )
            ->groupBy('product_id')
            ->having('quantity', '>=', 1);

        //shopとproductが販売中のものだけ取得
        $products = DB::table('products')
            //productsテーブルのidがstock(在庫の合計が1以上)テーブルのproduct_idに一致するレコードを結合
            ->joinSub($stocks, 'stock', function ($join) {
                $join->on('products.id', '=', 'stock.product_id');
            })
            //productsテーブルのshop_idがshopsテーブルのidに一致するレコードを結合
            ->join('shops', 'products.shop_id', '=', 'shops.id')
            //カテゴリーとimageを取得するためにsecondary_categoriesテーブルとimagesテーブルを結合
            ->join(
                'secondary_categories',
                'products.secondary_category_id',
                '=',
                'secondary_categories.id'
            )
            ->join('images as image1', 'products.image1', '=', 'image1.id')
            ->join('images as image2', 'products.image2', '=', 'image2.id')
            ->join('images as image3', 'products.image3', '=', 'image3.id')
            ->join('images as image4', 'products.image4', '=', 'image4.id')
            //shopsテーブルでis_sellingがtrueのものだけを取得
            ->where('shops.is_selling', true)
            //productsテーブルでis_sellingがtrueのものだけ取得
            ->where('products.is_selling', true)
            //productsテーブルからid,name,price,sort_order,information。secondary_categoriesテーブルからcategory名。imagesテーブルからfilenameを取得
            ->select(
                'products.id as id',
                'products.name as name',
                'products.price',
                'products.sort_order as sort_order',
                'products.information',
                'secondary_categories.name as category',
                'image1.filename as filename'
            )
            ->get();

        return view(
            'user.index',
            compact('products')
        );
    }
}
