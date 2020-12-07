<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class OrderController extends ApiController
{
	public function orders(Request $request)
	{
		$model = new Order();
		$where = array(
					'status'=>$request->status,
					'customer_id'=>$request->id
				);
		$data = $model->getOrder($where);
		return response()->json([
            'status_code' => 200,
            'status' => 'success',
            'message' => 'Successfully Load Data',
            'data' => $data
        ], 200);
	}

    public function order_detail(Request $request)
    {
        # code...
        $model = new Order();
        $where = array(
                    'order_id'=>$request->order_id
                );
        $data = $model->getOrderDetail($where)->first();
        return response()->json([
            'status_code' => 200,
            'status' => 'success',
            'message' => 'Successfully Load Data',
            'data' => $data
        ], 200);
    }

    public function layanan_laundry(Request $request)
    {
        # code...
        $model = new Order();
        $where = array(
                    'type'=>'Laundry'
                );
        $data = $model->getLayanan($where);
        return response()->json([
            'status_code' => 200,
            'status' => 'success',
            'message' => 'Successfully Load Data',
            'data' => $data
        ], 200);
    }

    public function layanan_service(Request $request)
    {
        # code...
        $model = new Order();
        $where = array(
                    'type'=>'Service'
                );
        $data = $model->getLayanan($where);
        return response()->json([
            'status_code' => 200,
            'status' => 'success',
            'message' => 'Successfully Load Data',
            'data' => $data
        ], 200);
    }

	public function save_bookservice(Request $request)
	{
		# code...
		$this->validate($request, [
    		'id' => 'required',
            'partner' => 'required',
            'layanan' => 'required',
            'type' => 'required',
            'tanggal' => 'required',
            'qty' => 'required',
            'jam' => 'required',
            'alamat' => 'required'
        ]);
        $layanan = explode("#", $request->layanan);
		
		
        $unit = "";
        if($request->type=='Cleaning'){
        	$unit = "Jam";
        }elseif ($request->type=='Laundry') {
        	# code...
        	$unit = "Kg";
        }
        $shecdule = date('Y-m-d H:i:s',strtotime(date('Y-m-d',strtotime($request->tanggal)).' '.date('H:i:s',strtotime($request->jam))));

        DB::beginTransaction();
		$order_id = Uuid::uuid4();
        try {
            //we need to split address to Province/Kabupaten/Kecamatan
            $address_array = array_map('trim', explode(',', $request->alamat));
            $aparts = count($address_array);
            $negara = $aparts>0 ? $address_array[$aparts-1]:"";
            $provinsi = $aparts>1 ? $address_array[$aparts-2]:"";
            $kabupaten = $aparts>2 ? $address_array[$aparts-3]:"";
            $kecamatan = $aparts>3 ? $address_array[$aparts-4]:"";
            $kelurahan = $aparts>4 ? $address_array[$aparts-5]:"";
            //get largest province number + increment

            //insert provinsi first
            $id_prov = 0;
            if ($negara!=""){
            DB::statement("INSERT INTO provinsi (nama)
                            SELECT * FROM (SELECT '$provinsi') AS tmp
                            WHERE NOT EXISTS (
                                SELECT nama FROM provinsi WHERE nama = '$provinsi'
                            ) LIMIT 1;
            ");
            //get provinsi ID
            $id_prov = DB::table('provinsi')
                                ->where('nama', '=', $provinsi)
                                ->first()->id_prov;
            }
            //insert kabupaten first
            $id_kab =0;
            if($kabupaten!=""){
            DB::statement("INSERT INTO kabupaten (id_prov,nama)
                            SELECT * FROM (SELECT '$id_prov','$kabupaten') AS tmp
                            WHERE NOT EXISTS (
                                SELECT nama FROM kabupaten WHERE nama = '$kabupaten' and id_prov = '$id_prov'
                            ) LIMIT 1;
            ");
            //get kabupaten ID
            $id_kab = DB::table('kabupaten')
                                ->where('nama', '=', $kabupaten)
                                ->first()->id_kab;
            }
            //insert kecamatan first
            $id_kec=0;
            if($kecamatan!=""){
                DB::statement("INSERT INTO kecamatan (id_kab,nama)
                                SELECT * FROM (SELECT '$id_kab','$kecamatan') AS tmp
                                WHERE NOT EXISTS (
                                    SELECT nama FROM kecamatan WHERE nama = '$kecamatan' and id_kab = '$id_kab'
                                ) LIMIT 1;
                ");
                //get kecamatan ID
                $id_kec = DB::table('kecamatan')
                                    ->where('nama', '=', $kecamatan)
                                    ->first()->id_kec;
            }
            $id_kel=0;
            if($kelurahan!=""){
                //insert kelurahan first
                DB::statement("INSERT INTO kelurahan (id_kec,nama)
                                SELECT * FROM (SELECT $id_kec,'$kelurahan') AS tmp
                                WHERE NOT EXISTS (
                                    SELECT nama FROM kelurahan WHERE nama = '$kelurahan' and id_kec = $id_kec
                                ) LIMIT 1;
                ");

                //get kelurahan ID
                $id_kel = DB::table('kelurahan')
                                    ->where('nama', '=', $kelurahan)
                                    ->first()->id_kel;
            }
        	$data = array(
        		'order_id' => $order_id,
        		'order_number' => mt_rand(1000, 9999),
            	'service_type' => $request->type,
            	'customer_id' => $request->id,
            	'partner_id' => 0,
                'layanan_id' => $layanan[0],
            	'schedule_datetime' => $shecdule,
            	'qty' => $request->qty,
            	'unit' => $unit,
            	'amount' => $request->qty*$layanan[1],
            	'address' => $request->alamat,
                'kabupaten_id'=> $id_kab,
                'kecamatan_id'=> $id_kec,
                'latitude'=> $request->lat,
                'longitude'=> $request->lng,
            	'address_note' => $request->note,
            	'status' => 'Pending',
            	'created_at'=> date('Y-m-d H:i:s')
        	);
			
			
        	Order::create($data);
        }catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status_code' => 422,
                'status' => 'error',
                'message' => 'Unknown Error',
                'data' => $e->getMessage()
            ], 422);
        }

        DB::commit();
        //$order_id = Order::orderBy('created_at', 'DESC')->limit(1)->value('order_id');
		
		$arraynumber = array('628978102574','6285720221119','6282299160032');
            for($x=0;$x<count($arraynumber);$x++){

            $response = Http::post('https://app.wapibot.com/api/send/text', [
                "apikey" => "49bbca103bc98c2d2eb5bf3eb7c11e6df3b35437",
                "to" => $arraynumber[$x],
                "message"  => "testing cc lot of number by wapibot"
            ]);
			}
		
		//$response = Http::post('https://app.wapibot.com/api/send/text', [
    				//"apikey" => "49bbca103bc98c2d2eb5bf3eb7c11e6df3b35437",
                	//"to" => 628978102574,
                	//"message"  => "Ada Pesanan Order"
		//]);
		
        return response()->json([
            'status_code' => 200,
            'status' => 'success',
            'message' => 'Successfully Bookservice',
            'order_id' => $order_id
        ], 200);
		
	}
}
