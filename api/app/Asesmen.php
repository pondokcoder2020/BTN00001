<?php

namespace PondokCoder;

use PondokCoder\Query as Query;
use PondokCoder\QueryException as QueryException;
use PondokCoder\Poli as Poli;
use PondokCoder\Penjamin as Penjamin;
use PondokCoder\Tindakan as Tindakan;
use PondokCoder\Inventori as Inventori;
use PondokCoder\Utility as Utility;
use PondokCoder\Antrian as Antrian;


class Asesmen extends Utility {
	static $pdo;
	static $query;
	
	protected static function getConn(){
		return self::$pdo;
	}

	public function __construct($connection) {
		self::$pdo = $connection;
		self::$query = new Query(self::$pdo);
	}

	public function __GET__($parameter = array()) {
		try {
			switch($parameter[1]) {
				case 'antrian-detail':
					return self::get_asesmen_medis($parameter[2]);
					break;

				case 'asesmen-rawat-detail':
					return self::get_asesmen_rawat($parameter[2]);
					break;

				case 'antrian-asesmen-rawat':
					return self::get_antrian_asesmen_rawat();
					break;

				case 'antrian-asesmen-medis':
					return self::get_antrian_asesmen_medis();
					break;

				default:
					return self::get_asesmen_medis($parameter[2]);
			}
		} catch (QueryException $e) {
			return 'Error => ' . $e;
		}
	}

	public function __POST__($parameter = array()) {
		try {
			switch($parameter['request']) {
				case 'update_asesmen_medis':
					return self::update_asesmen_medis($parameter);
					break;

				case 'update_asesmen_rawat':
					return self::update_asesmen_rawat($parameter);
					break;

				default:
					return 'Unknown request';
			}
		} catch (QueryException $e) {
			return 'Error => ' . $e;
		}
	}

	private function get_asesmen_medis($parameter) { //uid antrian
		//prepare antrian
		$antrian = self::$query->select('antrian', array(
			'uid',
			'kunjungan',
			'prioritas',
			'pasien',
			'departemen',
			'dokter',
			'penjamin'
		))
		->where(array(
			'antrian.uid' => '= ?',
			'AND',
			'antrian.deleted_at' => 'IS NULL'
		), array(
			$parameter
		))
		->execute();

		if(count($antrian['response_data']) > 0) {
			//Poli Info
			$Poli = new Poli(self::$pdo);
			$PoliDetail = $Poli::get_poli_detail($antrian['response_data'][0]['departemen'])['response_data'][0];


			$Rawat = self::$query->select('asesmen_rawat_' . $PoliDetail['poli_asesmen'], array(
				'uid'
			))
			->where(array(
				'asesmen_rawat_' . $PoliDetail['poli_asesmen'] . '.antrian' => '= ?'
			), array(
				$antrian['response_data'][0]['uid']
			))
			->execute();

			$data = self::$query->select('asesmen_medis_' . $PoliDetail['poli_asesmen'], array(
				'uid',
				'kunjungan',
				'antrian',
				'pasien',
				'dokter',
				'keluhan_utama',
				'keluhan_tambahan',
				'tekanan_darah',
				'nadi',
				'pernafasan',
				'berat_badan',
				'tinggi_badan',
				'lingkar_lengan_atas',
				'pemeriksaan_fisik',
				'diagnosa_kerja',
				'diagnosa_banding',
				'planning',
				'asesmen',
				'suhu',
				'icd10_kerja',
				'icd10_banding',
				'created_at',
				'updated_at'
			))
			->where(array(
				'asesmen_medis_' . $PoliDetail['poli_asesmen'] . '.deleted_at' => 'IS NULL',
				'AND',
				'asesmen_medis_' . $PoliDetail['poli_asesmen'] . '.kunjungan' => '= ?',
				'AND',
				'asesmen_medis_' . $PoliDetail['poli_asesmen'] . '.antrian' => '= ?',
				'AND',
				'asesmen_medis_' . $PoliDetail['poli_asesmen'] . '.pasien' => '= ?',
				'AND',
				'asesmen_medis_' . $PoliDetail['poli_asesmen'] . '.dokter' => '= ?'
			), array(
				$antrian['response_data'][0]['kunjungan'],
				$antrian['response_data'][0]['uid'],
				$antrian['response_data'][0]['pasien'],
				$antrian['response_data'][0]['dokter']
			))
			->execute();
			if(count($data['response_data']) > 0) {
				//Tindakan Detail
				$tindakan = self::$query->select('asesmen_tindakan', array(
					'tindakan'
				))
				->where(array(
					'asesmen_tindakan.deleted_at' => 'IS NULL',
					'AND',
					'asesmen_tindakan.kunjungan' => '= ?',
					'AND',
					'asesmen_tindakan.asesmen' => '= ?'
				), array(
					$antrian['response_data'][0]['kunjungan'],
					$data['response_data'][0]['asesmen']
				))
				->execute();
				foreach ($tindakan['response_data'] as $key => $value) {
					$Tindakan = new Tindakan(self::$pdo);
					$tindakan['response_data'][$key] = $Tindakan::get_tindakan_detail($value['tindakan'])['response_data'][0];
				}
				$data['response_data'][0]['tindakan'] = $tindakan['response_data'];

				//Resep Detail
				$resep = self::$query->select('resep', array(
					'uid',
					'keterangan',
					'keterangan_racikan'
				))
				->where(array(
					'resep.deleted_at' => 'IS NULL',
					'AND',
					'resep.kunjungan' => '= ?',
					'AND',
					'resep.antrian' => '= ?',
					'AND',
					'resep.asesmen' => '= ?',
					'AND',
					'resep.dokter' => '= ?',
					'AND',
					'resep.pasien' => '= ?',
					'AND',
					'resep.status_resep' => '= ?'
				), array(
					$antrian['response_data'][0]['kunjungan'],
					$antrian['response_data'][0]['uid'],
					$data['response_data'][0]['asesmen'],
					$data['response_data'][0]['dokter'],
					$data['response_data'][0]['pasien'],
					'N'
				))
				->execute();
				$racikanData = array();
				foreach ($resep['response_data'] as $key => $value) {
					//GET Resep Detail
					$resepDetail = self::$query->select('resep_detail', array(
						'id',
						'resep',
						'obat',
						'harga',
						'signa_qty',
						'signa_pakai',
						'keterangan',
						'aturan_pakai',
						'qty',
						'satuan',
						'created_at',
						'updated_at'
					))
					->where(array(
						'resep_detail.deleted_at' => 'IS NULL',
						'AND',
						'resep_detail.resep' => '= ?'
					), array(
						$value['uid']
					))
					->execute();
					$resep['response_data'][$key]['resep_detail'] = $resepDetail['response_data'];

					//Racikan Detail
					$racikan = self::$query->select('racikan', array(
						'uid',
						'asesmen',
						//'resep',
						'kode',
						'keterangan',
						'aturan_pakai',
						'signa_qty',
						'signa_pakai',
						'qty',
						'total'
					))
					->where(array(
						'racikan.asesmen' => '= ?',
						'AND',
						'racikan.deleted_at' => 'IS NULL'
					), array(
						$data['response_data'][0]['asesmen']
					))
					->execute();

					foreach ($racikan['response_data'] as $RacikanKey => $RacikanValue) {
						$RacikanDetailData = self::$query->select('racikan_detail', array(
							'asesmen',
							//'resep',
							'obat',
							'ratio',
							'pembulatan',
							'takar_bulat',
							'takar_decimal',
							'harga',
							'racikan',
							'penjamin'
						))
						->where(array(
							'racikan_detail.deleted_at' => 'IS NULL',
							/*'AND',
							'racikan_detail.resep' => '= ?',*/
							'AND',
							'racikan_detail.racikan' => '= ?'
						), array(
							//$value['uid'],
							$RacikanValue['uid']
						))
						->execute();

						foreach ($RacikanDetailData['response_data'] as $RVIKey => $RVIValue) {
							$InventoriObat = new Inventori(self::$pdo);
							$RacikanDetailData['response_data'][$RVIKey]['obat_detail'] = $InventoriObat::get_item_detail($RVIValue['obat'])['response_data'][0];
						}

						$RacikanValue['item'] = $RacikanDetailData['response_data'];

						array_push($racikanData, $RacikanValue);
					}
				}

				$data['response_data'][0]['racikan'] = $racikanData;
				$data['response_data'][0]['resep'] = $resep['response_data'];
				$data['response_data'][0]['asesmen_rawat'] = $Rawat['response_data'][0]['uid'];
				return $data;
			} else {
				$Rawat = self::$query->select('asesmen_rawat_' . $PoliDetail['poli_asesmen'], array(
					'uid'
				))
				->where(array(
					'asesmen_rawat_' . $PoliDetail['poli_asesmen'] . '.antrian' => '= ?'
				), array(
					$antrian['response_data'][0]['uid']
				))
				->execute();
				$antrian['response_data'][0]['asesmen_rawat'] = $Rawat['response_data'][0]['uid'];
				return $antrian;
			}
		} else {
			return $antrian;
		}
	}

	private function update_asesmen_medis($parameter) {
		$Authorization = new Authorization();
		$UserData = $Authorization::readBearerToken($parameter['access_token']);
		$MasterUID = '';
		//Prepare Poli
		$Poli = new Poli(self::$pdo);
		$PoliDetail = $Poli::get_poli_detail($parameter['poli'])['response_data'][0];

		//Check
		$check = self::$query->select('asesmen', array(
			'uid'
		))
		->where(array(
			'asesmen.deleted_at' => 'IS NULL',
			'AND',
			'asesmen.poli' => '= ?',
			'AND',
			'asesmen.kunjungan' => '= ?',
			'AND',
			'asesmen.antrian' => '= ?',
			'AND',
			'asesmen.pasien' => '= ?',
			'AND',
			'asesmen.dokter' => '= ?'
		), array(
			$parameter['poli'],
			$parameter['kunjungan'],
			$parameter['antrian'],
			$parameter['pasien'],
			$UserData['data']->uid
		))
		->execute();

		if(count($check['response_data']) > 0) {
			$MasterUID = $check['response_data'][0]['uid'];
			$returnResponse = array();

			//Poli Asesmen Check
			$poli_check = self::$query->select('asesmen_medis_' . $PoliDetail['poli_asesmen'], array(
				'uid'
			))
			->where(array(
				'asesmen_medis_' . $PoliDetail['poli_asesmen'] . '.deleted_at' => 'IS NULL',
				'AND',
				'asesmen_medis_' . $PoliDetail['poli_asesmen'] . '.asesmen' => '= ?'
			), array(
				$check['response_data'][0]['uid']
			))
			->execute();

			if(count($poli_check['response_data']) > 0) {
				//update
				$worker = self::$query->update('asesmen_medis_' . $PoliDetail['poli_asesmen'], array(
					'keluhan_utama' => $parameter['keluhan_utama'],
					'keluhan_tambahan' => $parameter['keluhan_tambahan'],
					'tekanan_darah' => floatval($parameter['tekanan_darah']),
					'nadi' => floatval($parameter['nadi']),
					'suhu' => floatval($parameter['suhu']),
					'pernafasan' => floatval($parameter['pernafasan']),
					'berat_badan' => floatval($parameter['berat_badan']),
					'tinggi_badan' => floatval($parameter['tinggi_badan']),
					'lingkar_lengan_atas' => floatval($parameter['lingkar_lengan_atas']),
					'pemeriksaan_fisik' => $parameter['pemeriksaan_fisik'],
					'icd10_kerja' => intval($parameter['icd10_kerja']),
					'diagnosa_kerja' => $parameter['diagnosa_kerja'],
					'icd10_banding' => intval($parameter['icd10_banding']),
					'diagnosa_banding' => $parameter['diagnosa_banding'],
					'planning' => $parameter['planning'],
					'updated_at' => parent::format_date()
				))
				->where(array(
					'asesmen_medis_' . $PoliDetail['poli_asesmen'] . '.deleted_at' => 'IS NULL',
					'AND',
					'asesmen_medis_' . $PoliDetail['poli_asesmen'] . '.uid' => '= ?',
					'AND',
					'asesmen_medis_' . $PoliDetail['poli_asesmen'] . '.kunjungan' => '= ?',
					'AND',
					'asesmen_medis_' . $PoliDetail['poli_asesmen'] . '.antrian' => '= ?',
					'AND',
					'asesmen_medis_' . $PoliDetail['poli_asesmen'] . '.pasien' => '= ?',
					'AND',
					'asesmen_medis_' . $PoliDetail['poli_asesmen'] . '.dokter' => '= ?',
					'AND',
					'asesmen_medis_' . $PoliDetail['poli_asesmen'] . '.asesmen' => '= ?'
				), array(
					$poli_check['response_data'][0]['uid'],
					$parameter['kunjungan'],
					$parameter['antrian'],
					$parameter['pasien'],
					$UserData['data']->uid,
					$check['response_data'][0]['uid']
				))
				->execute();

				if($worker['response_result'] > 0) {
					//Update asesmen medis
					
					$updateAsesmen = self::$query->update('asesmen', array(
						'status' => 'D'
					))
					->where(array(
						'asesmen.uid' => '= ?'
					), array(
						$MasterUID
					))
					->execute();

					$log = parent::log(array(
						'type'=>'activity',
						'column'=>array(
							'unique_target',
							'user_uid',
							'table_name',
							'action',
							'logged_at',
							'status',
							'login_id'
						),
						'value'=>array(
							$check['response_data'][0]['uid'],
							$UserData['data']->uid,
							'asesmen',
							'U',
							parent::format_date(),
							'N',
							$UserData['data']->log_id
						),
						'class'=>__CLASS__
					));
				}
			} else {
				$worker = self::new_asesmen($parameter, $check['response_data'][0]['uid'], $PoliDetail['poli_asesmen']);
			}

			$returnResponse = $worker;
		} else {
			//new asesmen
			$NewAsesmen = parent::gen_uuid();
			$MasterUID = $NewAsesmen;
			$asesmen_poli = self::$query->insert('asesmen', array(
				'uid' => $NewAsesmen,
				'poli' => $parameter['poli'],
				'kunjungan' => $parameter['kunjungan'],
				'antrian' => $parameter['antrian'],
				'pasien' => $parameter['pasien'],
				'dokter' => $UserData['data']->uid,
				'created_at' => parent::format_date(),
				'updated_at' => parent::format_date()
			))
			->execute();
			if($asesmen_poli['response_result'] > 0) {

				$log = parent::log(array(
					'type'=>'activity',
					'column'=>array(
						'unique_target',
						'user_uid',
						'table_name',
						'action',
						'logged_at',
						'status',
						'login_id'
					),
					'value'=>array(
						$NewAsesmen,
						$UserData['data']->uid,
						'asesmen',
						'I',
						parent::format_date(),
						'N',
						$UserData['data']->log_id
					),
					'class'=>__CLASS__
				));

				$worker = self::new_asesmen($parameter, $NewAsesmen, $PoliDetail['poli_asesmen']);

				$returnResponse = $worker;
			} else {
				$returnResponse = $asesmen_poli;
			}
		}





		//Tindakan Management
		$returnResponse['tindakan_response'] = self::set_tindakan_asesment($parameter['tindakan'], $MasterUID);

		//Resep dan Racikan
		$returnResponse['resep_response'] = self::set_resep_asesment($parameter, $MasterUID);

		return $returnResponse;
	}

	private function set_resep_asesment($parameter, $MasterAsesmen) {
		$Authorization = new Authorization();
		$UserData = $Authorization::readBearerToken($parameter['access_token']);

		$check = self::$query->select('resep', array(
			'uid'
		))
		->where(array(
			'resep.kunjungan' => '= ?',
			'AND',
			'resep.antrian' => '= ?',
			'AND',
			'resep.asesmen' => '= ?',
			'AND',
			'resep.dokter' => '= ?',
			'AND',
			'resep.pasien' => '= ?',
			'AND',
			'resep.status_resep' => '= ?',
			'AND',
			'resep.deleted_at' => 'IS NULL'
		), array(
			$parameter['kunjungan'],
			$parameter['antrian'],
			$MasterAsesmen,
			$UserData['data']->uid,
			$parameter['pasien'],
			'N'
		))
		->execute();
		
		if(count($check['response_data']) > 0) {
			$uid = $check['response_data'][0]['uid'];

			//Update resep master
			$resepUpdate = self::$query->update('resep', array(
				'keterangan' => $parameter['keteranganResep'],
				'keterangan_racikan' => $parameter['keteranganRacikan']
			))
			->where(array(
				'resep.uid' => '= ?',
				'AND',
				'resep.deleted_at' => 'IS NULL'
			), array(
				$uid
			))
			->execute();

			//Reset Resep Detail
			$resetResep = self::$query->update('resep_detail', array(
				'deleted_at' => parent::format_date()
			))
			->where(array(
				'resep_detail.resep' => '= ?'
			), array(
				$uid
			))
			->execute();

			//Update Detail Resep
			$used_obat = array();
			$old_resep_detail = array();
			$detail_check = self::$query->select('resep_detail', array(
				'id',
				'obat'
			))
			->where(array(
				'resep_detail.resep' => '= ?'
			), array(
				$uid
			))
			->execute();

			foreach ($detail_check['response_data'] as $key => $value) {
				if(!in_array($value['obat'], $used_obat)) {
					array_push($used_obat, $value['obat']);
					array_push($old_resep_detail, $value);
				}
			}

			$resepProcess = array();

			foreach ($parameter['resep'] as $key => $value) {
				//Prepare Data Obat
				$ObatDetail = new Inventori(self::$pdo);
				$ObatInfo = $ObatDetail::get_item_detail($value['obat'])['response_data'][0];

				if(in_array($value['obat'], $used_obat)) {
					$worker = self::$query->update('resep_detail', array(
						'signa_qty' => $value['signaKonsumsi'],
						'signa_pakai' => $value['signaTakar'],
						'qty' => $value['signaHari'],
						'aturan_pakai' => $value['aturanPakai'],
						'keterangan' => $value['keteranganPerObat'],
						'updated_at'=> parent::format_date(),
						'deleted_at' => NULL
					))
					->where(array(
						'resep_detail.resep' => '= ?',
						'AND',
						'resep_detail.obat' => '= ?'
					), array(
						$uid,
						$value['obat']
					))
					->execute();
				} else {
					$worker = self::$query->insert('resep_detail', array(
						'resep' => $uid,
						'obat' => $value['obat'],
						'harga' => 0,
						'signa_qty' => $value['signaKonsumsi'],
						'signa_pakai' => $value['signaTakar'],
						'qty' => $value['signaHari'],
						'satuan' => $ObatInfo['satuan_terkecil'],
						'aturan_pakai' => $value['aturanPakai'],
						'keterangan' => $value['keteranganPerObat'],
						'created_at' => parent::format_date(),
						'updated_at'=> parent::format_date()
					))
					->execute();
				}
				array_push($resepProcess, $worker);
			}




			//Reset Racikan
			$racikReset = self::$query->update('racikan', array(
				'deleted_at' => parent::format_date()
			))
			->where(array(
				'racikan.asesmen' => '= ?'
			), array(
				$MasterAsesmen
			))
			->execute();


			//Filter #1
			$racikanOld = self::$query->select('racikan', array(
				'uid'
			))
			->where(array(
				'racikan.asesmen' => '= ?'
			), array(
				$MasterAsesmen
			))
			->execute();

			$racikanError = array();

			foreach ($racikanOld['response_data'] as $key => $value) {
				$racikanUpdate = self::$query->update('racikan', array(
					'kode' => $parameter['racikan'][$key]['nama'],
					'aturan_pakai' => intval($parameter['racikan'][$key]['aturanPakai']),
					'keterangan' => $parameter['racikan'][$key]['keterangan'],
					'signa_qty' => $parameter['racikan'][$key]['signaKonsumsi'],
					'signa_pakai' => $parameter['racikan'][$key]['signaTakar'],
					'qty' => $parameter['racikan'][$key]['signaHari'],
					'deleted_at' => NULL
				))
				->where(array(
					'racikan.uid' => '= ?'
				), array(
					$value['uid']
				))
				->execute();
				if($racikanUpdate['response_result'] > 0) {
					//Reset Racikan Detail
					$resetRacikanDetail = self::$query->update('racikan_detail', array(
						'deleted_at' => parent::format_date()
					))
					->where(array(
						/*'racikan_detail.resep' => '= ?',
						'AND',*/
						'racikan_detail.racikan' => '= ?',
						'AND',
						'racikan_detail.asesmen' => '= ?'
					), array(
						//$uid,
						$value['uid'],
						$MasterAsesmen
					))
					->execute();

					//Old Racikan Detail
					$checkRacikanDetail = self::$query->select('racikan_detail', array(
						'id',
						'obat'
					))
					->where(array(
						/*'racikan_detail.resep' => '= ?',
						'AND',*/
						'racikan_detail.racikan' => '= ?',
						'AND',
						'racikan_detail.asesmen' => '= ?'
					), array(
						//$uid,
						$value['uid'],
						$MasterAsesmen
					))
					->execute();

					$oldRacikanDetail = array();
					$usedRacikanDetail = array();
					foreach ($checkRacikanDetail['response_data'] as $RDKey => $RDValue) {
						if(!in_array($RDValue['obat'], $usedRacikanDetail)) {
							array_push($usedRacikanDetail, $RDValue['obat']);
							array_push($oldRacikanDetail, $RDValue);
						}
					}

					foreach ($parameter['racikan'][$key]['item'] as $RDIKey => $RDIValue) {
						if(in_array($RDIValue['obat'], $usedRacikanDetail)) {
							$racikanDetailWorker = self::$query->update('racikan_detail', array(
								'obat' => $RDIValue['obat'],
								'ratio' => $RDIValue['takaran'],
								'penjamin' => $parameter['penjamin'],
								'takar_bulat' => $RDIValue['takaranBulat'],
								'takar_decimal' => $RDIValue['takaranDecimalText'],
								'pembulatan' => ceil($RDIValue['takaran']),
								'deleted_at' => NULL
							))
							->where(array(
								/*'racikan_detail.resep' => '= ?',
								'AND',*/
								'racikan_detail.racikan' => '= ?',
								'AND',
								'racikan_detail.asesmen' => '= ?',
								'AND',
								'racikan_detail.obat' => '= ?'
							), array(
								//$uid,
								$value['uid'],
								$MasterAsesmen,
								$RDIValue['obat']
							))
							->execute();
						} else {
							$racikanDetailWorker = self::$query->insert('racikan_detail', array(
								'asesmen' => $MasterAsesmen,
								//'resep' => $uid,
								'obat' => $RDIValue['obat'],
								'pembulatan' => ceil($RDIValue['takaran']),
								'takar_bulat' => $RDIValue['takaranBulat'],
								'takar_decimal' => $RDIValue['takaranDecimalText'],
								'harga' => 0,
								'created_at' => parent::format_date(),
								'updated_at' => parent::format_date(),
								'racikan' => $value['uid'],
								'ratio' => $RDIValue['takaran']
							))
							->execute();
						}
					}

					array_push($racikanError, $racikanDetailWorker);

					//Unset processed data from parameter
					unset($parameter['racikan'][$key]);
				} else {
					//
				}
			}

			//UnProcessed Racikan
			foreach ($parameter['racikan'] as $key => $value) {
				$newRacikanUID = parent::gen_uuid();
				$newRacikan = self::$query->insert('racikan', array(
					'uid' => $newRacikanUID,
					'asesmen' => $MasterAsesmen,
					'kode' => $value['nama'],
					'total' => 0,
					'signa_qty' => $value['signaKonsumsi'],
					'signa_pakai' => $value['signaTakar'],
					'aturan_pakai' => intval($value['aturanPakai']),
					'qty' => $value['signaHari'],
					'created_at' => parent::format_date(),
					'updated_at' => parent::format_date()
				))
				->execute();

				if($newRacikan['response_result'] > 0) {
					$racikanDetail = $parameter['racikan']['item'];
					foreach ($racikanDetail as $RDKey => $RDValue) {
						$detailRacikan = self::$query->insert('racikan_detail', array(
							'asesmen' => $MasterAsesmen,
							'racikan' => $newRacikanUID,
							//'resep' => $uid,
							'obat' => $RDValue['obat'],
							'ratio' => $RDValue['takaran'],
							'pembulatan' => ceil($RDValue['takaran']),
							'takar_bulat' => $RDIValue['takaranBulat'],
							'takar_decimal' => $RDIValue['takaranDecimalText'],
							'harga' => 0,
							'penjamin' => '',
							'created_at' => parent::format_date(),
							'updated_at' => parent::format_date(),
						))
						->execute();
						array_push($racikanError, $detailRacikan);
					}
				} else {
					array_push($racikanError, $newRacikan);
				}
			}
			//return $racikanError;

		} else {

			//New Resep
			$uid = parent::gen_uuid();
			$newResep = self::$query->insert('resep',array(
				'uid' => $uid,
				'kunjungan' => $parameter['kunjungan'],
				'antrian' => $parameter['antrian'],
				'keterangan' => $parameter['keteranganResep'],
				'keterangan_racikan' => $parameter['keteranganRacikan'],
				'asesmen' => $MasterAsesmen,
				'dokter' => $UserData['data']->uid,
				'pasien' => $parameter['pasien'],
				'total' => 0,
				'status_resep' => 'N',
				'created_at' => parent::format_date(),
				'updated_at' => parent::format_date()
			))
			->execute();

			if($newResep['response_result'] > 0) {
				$resep_detail_error = array();
				//SetDetail
				foreach ($parameter['resep'] as $key => $value) {
					$ObatDetail = new Inventori(self::$pdo);
					$ObatInfo = $ObatDetail::get_item_detail($value['obat'])['response_data'][0];

					$newResepDetail = self::$query->insert('resep_detail', array(
						'resep' => $uid,
						'obat' => $value['obat'],
						'aturan_pakai' => $value['aturanPakai'],
						'harga' => 0,
						'signa_qty' => $value['signaKonsumsi'],
						'signa_pakai' => $value['signaTakar'],
						'qty' => $value['signaHari'],
						'satuan' => $ObatInfo['satuan_terkecil'],
						'created_at' => parent::format_date(),
						'updated_at' => parent::format_date(),
						'keterangan' => $value['keteranganPerObat']
					))
					->execute();
					array_push($resep_detail_error, $newResepDetail);
				}

				foreach ($parameter['racikan'] as $key => $value) {
					$uid_racikan = parent::gen_uuid();
					$newRacikan = self::$query->insert('racikan', array(
						'uid' => $uid_racikan,
						'asesmen' => $MasterAsesmen,
						//'resep' => $uid,
						'kode' => $value['nama'],
						'signa_qty' => $value['signaKonsumsi'],
						'signa_pakai' => $value['signaTakar'],
						'aturan_pakai' => intval($value['aturanPakai']),
						'qty' => $value['signaHari'],
						'total' => 0,
						'created_at' => parent::format_date(),
						'updated_at' => parent::format_date()
					))
					->execute();

					if($newRacikan['response_result'] > 0) {
						/*$newResepDetail = self::$pdo->insert('resep_detail', array(
							'resep' => $uid,
							'obat' => $uid_racikan,
							'aturan_pakai' => $value['aturanPakai'],
							'harga' => 0,
							'signa_qty' => $value['signaKonsumsi'],
							'signa_pakai' => $value['signaTakar'],
							'qty' => $value['signaHari'],
							'satuan' => '',
							'created_at' => parent::format_date(),
							'updated_at' => parent::format_date()
						))
						->execute();*/

						//Set Racikan Detail
						foreach ($value['item'] as $RIKey => $RIValue) {
							$newRacikanDetail = self::$query->insert('racikan_detail', array(
								'asesmen' => $MasterAsesmen,
								//'resep' => $uid_racikan,
								'obat' => $RIValue['obat'],
								'ratio' => $RIValue['takaran'],
								'pembulatan' => ceil($RIValue['takaran']),
								'takar_bulat' => $RIValue['takaranBulat'],
								'takar_decimal' => $RIValue['takaranDecimalText'],
								'harga' => 0,
								'racikan' => $uid_racikan,
								'created_at' => parent::format_date(),
								'updated_at' => parent::format_date()
							))
							->execute();
						}
					}
				}
			}
			return $newResep;
		}
	}

	private function set_tindakan_asesment($parameter, $MasterAsesmen) {
		$requested = array();
		foreach ($parameter as $key => $value) {
			if(!in_array($value['item'], $requested)) {
				array_push($requested, $value['item']);
			} else {
				array_push($requested, $value['item']);
			}
		}
		$returnResponse = array();
		$registered = array();

		$entry = self::$query->select('asesmen_tindakan', array(
			'uid',
			'tindakan'
		))
		->where(array(
			'asesmen_tindakan.asesmen' => '= ?'
		), array(
			$MasterAsesmen
		))
		->execute();

		foreach ($entry['response_data'] as $key => $value) {
			if(in_array($value['tindakan'], $requested)) {
				$activate = self::$query->update('asesmen_tindakan', array(
					'deleted_at' => NULL
				))
				->where(array(
					'asesmen_tindakan.asesmen' => '= ?',
					'AND',
					'asesmen_tindakan.tindakan' => '= ?'
				), array(
					$MasterAsesmen,
					$value['tindakan']
				))
				->execute();
				array_push($returnResponse, $activate);
			} else {
				$activate = self::$query->update('asesmen_tindakan', array(
					'deleted_at' => parent::format_date()
				))
				->where(array(
					'asesmen_tindakan.asesmen' => '= ?',
					'AND',
					'asesmen_tindakan.tindakan' => '= ?'
				), array(
					$MasterAsesmen,
					$value['tindakan']
				))
				->execute();
				array_push($returnResponse, $activate);
			}
			array_splice($requested, array_search($value['tindakan'], $requested), 1);
			array_splice($parameter, array_search($value['tindakan'], $requested), 1);
		}

		foreach ($parameter as $key => $value) {
			$Penjamin = self::$query
			->select('master_poli_tindakan_penjamin', array(
				'id',
				'harga',
				'uid_poli',
				'uid_tindakan',
				'uid_penjamin',
				'created_at',
				'updated_at'
			))
			->where(array(
				'master_poli_tindakan_penjamin.deleted_at' => 'IS NULL',
				'AND',
				'master_poli_tindakan_penjamin.uid_tindakan' => '= ?',
				'AND',
				'master_poli_tindakan_penjamin.uid_penjamin' => '= ?',
				'AND',
				'master_poli_tindakan_penjamin.uid_poli' => '= ?'
			), array(
				$value['item'],
				$value['penjamin'],
				$value['poli']
			))
			->execute();

			$new_asesmen_uid = parent::gen_uuid();
			$new_asesmen_tindakan = self::$query->insert('asesmen_tindakan', array(
				'uid' => $new_asesmen_uid,
				'kunjungan' => $value['kunjungan'],
				'antrian' => $value['antrian'],
				'asesmen' => $MasterAsesmen,
				'tindakan' => $value['item'],
				'penjamin' => $value['penjamin'],
				'harga' => $Penjamin['response_data'][0]['harga'],
				'created_at' => parent::format_date(),
				'updated_at' => parent::format_date()
			))
			->execute();
			array_push($returnResponse, $new_asesmen_tindakan);
		}

		return $returnResponse;
	}

	private function new_asesmen($parameter, $parent, $poli) {
		$Authorization = new Authorization();
		$UserData = $Authorization::readBearerToken($parameter['access_token']);
		$NewAsesmenPoli = parent::gen_uuid();
		//insert
		$worker = self::$query->insert('asesmen_medis_' . $poli, array(
			'uid' => $NewAsesmenPoli,
			'asesmen' => $parent,
			'kunjungan' => $parameter['kunjungan'],
			'antrian' => $parameter['antrian'],
			'pasien' => $parameter['pasien'],
			'dokter' => $UserData['data']->uid,
			'keluhan_utama' => $parameter['keluhan_utama'],
			'keluhan_tambahan' => $parameter['keluhan_tambahan'],
			'tekanan_darah' => floatval($parameter['tekanan_darah']),
			'nadi' => floatval($parameter['nadi']),
			'suhu' => floatval($parameter['suhu']),
			'pernafasan' => floatval($parameter['pernafasan']),
			'berat_badan' => floatval($parameter['berat_badan']),
			'tinggi_badan' => floatval($parameter['tinggi_badan']),
			'lingkar_lengan_atas' => floatval($parameter['lingkar_lengan_atas']),
			'pemeriksaan_fisik' => $parameter['pemeriksaan_fisik'],
			'icd10_kerja' => intval($parameter['icd10_kerja']),
			'diagnosa_kerja' => $parameter['diagnosa_kerja'],
			'icd10_banding' => intval($parameter['icd10_banding']),
			'diagnosa_banding' => $parameter['diagnosa_banding'],
			'planning' => $parameter['planning'],
			'created_at' => parent::format_date(),
			'updated_at' => parent::format_date()
		))
		->execute();

		if($worker['response_result'] > 0) {
			$log = parent::log(array(
				'type'=>'activity',
				'column'=>array(
					'unique_target',
					'user_uid',
					'table_name',
					'action',
					'new_value',
					'logged_at',
					'status',
					'login_id'
				),
				'value'=>array(
					$parent,
					$UserData['data']->uid,
					'asesmen_medis_' . $PoliDetail['poli_asesmen'],
					'I',
					json_encode($parameter),
					parent::format_date(),
					'N',
					$UserData['data']->log_id
				),
				'class'=>__CLASS__
			));
		}
		return $worker;
	}


	/*--------------------------- ASESMEN RAWAT-------------------------------*/
	private function get_asesmen_rawat($parameter){		//uid antrian
		$antrian = self::get_pasien_asesmen_rawat($parameter);
		$antrian['asesmen_rawat'] = [];

		if(count($antrian) > 0) {
			//Poli Info
			$Poli = new Poli(self::$pdo);
			$PoliDetail = $Poli::get_poli_detail($antrian['antrian']['departemen'])['response_data'][0];

			$data = self::$query
				->select('asesmen_rawat_' . $PoliDetail['poli_asesmen'], array('*'))
				->where(array(
						'asesmen_rawat_' . $PoliDetail['poli_asesmen'] . '.deleted_at' => 'IS NULL',
						'AND',
						'asesmen_rawat_' . $PoliDetail['poli_asesmen'] . '.kunjungan' => '= ?',
						'AND',
						'asesmen_rawat_' . $PoliDetail['poli_asesmen'] . '.antrian' => '= ?',
						'AND',
						'asesmen_rawat_' . $PoliDetail['poli_asesmen'] . '.pasien' => '= ?'
					), array(
						$antrian['antrian']['kunjungan'],
						$antrian['antrian']['uid'],
						$antrian['antrian']['uid_pasien']
					)
				)
				->execute();
			if ($data['response_result'] > 0){
				$antrian['asesmen_rawat'] = $data['response_data'][0];
			}
		}

		return $antrian;
	}

	private function update_asesmen_rawat($parameter){
		$Authorization = new Authorization();
		$UserData = $Authorization::readBearerToken($parameter['access_token']);
		$MasterUID = '';
		//Prepare Poli
		$Poli = new Poli(self::$pdo);
		$PoliDetail = $Poli::get_poli_detail($parameter['dataAntrian']['departemen'])['response_data'][0];

		//Check
		$check = self::$query->select('asesmen', array(
				'uid'
			))
			->where(array(
				'asesmen.deleted_at' => 'IS NULL',
				'AND',
				'asesmen.poli' => '= ?',
				'AND',
				'asesmen.kunjungan' => '= ?',
				'AND',
				'asesmen.antrian' => '= ?',
				'AND',
				'asesmen.pasien' => '= ?'
			), array(
				$parameter['dataAntrian']['departemen'],
				$parameter['dataAntrian']['kunjungan'],
				$parameter['dataAntrian']['uid'],
				$parameter['dataAntrian']['uid_pasien']
			))
			->execute();

		if(count($check['response_data']) > 0) {
			$MasterUID = $check['response_data'][0]['uid'];
			$returnResponse = array();

			//Poli Asesmen Rawat Check
			$poli_check = self::$query->select('asesmen_rawat_' . $PoliDetail['poli_asesmen'], array(
					'uid'
				))
				->where(array(
					'asesmen_rawat_' . $PoliDetail['poli_asesmen'] . '.deleted_at' => 'IS NULL',
					'AND',
					'asesmen_rawat_' . $PoliDetail['poli_asesmen'] . '.asesmen' => '= ?'
				), array(
					$check['response_data'][0]['uid']
				))
				->execute();

			if(count($poli_check['response_data']) > 0) {
				//update asesmen rawat --> sudah oke
				$parameter['dataObj']['updated_at'] = parent::format_date();

				$rawat = self::$query
					->update('asesmen_rawat_' . $PoliDetail['poli_asesmen'], $parameter['dataObj'])
					->where(array(
							'asesmen_rawat_' . $PoliDetail['poli_asesmen'] . '.deleted_at' => 'IS NULL',
							'AND',
							'asesmen_rawat_' . $PoliDetail['poli_asesmen'] . '.uid' => '= ?',
							'AND',
							'asesmen_rawat_' . $PoliDetail['poli_asesmen'] . '.kunjungan' => '= ?',
							'AND',
							'asesmen_rawat_' . $PoliDetail['poli_asesmen'] . '.antrian' => '= ?',
							'AND',
							'asesmen_rawat_' . $PoliDetail['poli_asesmen'] . '.pasien' => '= ?',
							'AND',
							'asesmen_rawat_' . $PoliDetail['poli_asesmen'] . '.asesmen' => '= ?'
						), array(
							$poli_check['response_data'][0]['uid'],
							$parameter['dataAntrian']['kunjungan'],
							$parameter['dataAntrian']['uid'],
							$parameter['dataAntrian']['uid_pasien'],
							$MasterUID
						)
					)
					->execute();

				if($rawat['response_result'] > 0) {
					$log = parent::log(array(
						'type'=>'activity',
						'column'=>array(
							'unique_target',
							'user_uid',
							'table_name',
							'action',
							'logged_at',
							'status',
							'login_id'
						),
						'value'=>array(
							$check['response_data'][0]['uid'],
							$UserData['data']->uid,
							'asesmen_rawat_' . $PoliDetail['poli_asesmen'],
							'U',
							parent::format_date(),
							'N',
							$UserData['data']->log_id
						),
						'class'=>__CLASS__
					));

					$updateAsesmen = self::$query
						->update('asesmen', array('status' => 'D'))
						->where(array('asesmen.uid' => '= ?'), array($MasterUID))
						->execute();
				}
			} else {
				//new asesmen rawat --> sudah oke
				$parameter['dataObj']['antrian'] = $parameter['dataAntrian']['uid'];
				$parameter['dataObj']['no_rm'] = $parameter['dataPasien']['no_rm'];
				$parameter['dataObj']['pasien'] = $parameter['dataAntrian']['uid_pasien'];
				$parameter['dataObj']['kunjungan'] = $parameter['dataAntrian']['kunjungan'];
				$parameter['dataObj']['departemen'] = $parameter['dataAntrian']['departemen'];
				
				$rawat = self::new_asesmen_rawat($parameter['dataObj'], $MasterUID, $PoliDetail['poli_asesmen']);
			}

			$returnResponse = $rawat;
		} else {
			//new asesmen --> sudah oke
			$NewAsesmen = parent::gen_uuid();
			$MasterUID = $NewAsesmen;
			$asesmen_poli = self::$query->insert('asesmen', array(
				'uid' => $NewAsesmen,
				'poli' => $parameter['dataAntrian']['departemen'],
				'kunjungan' => $parameter['dataAntrian']['kunjungan'],
				'antrian' => $parameter['dataAntrian']['uid'],
				'pasien' => $parameter['dataAntrian']['uid_pasien'],
				'dokter' => $parameter['dataAntrian']['dokter'],
				'created_at' => parent::format_date(),
				'updated_at' => parent::format_date()
			))
			->execute();

			if($asesmen_poli['response_result'] > 0) {

				$log = parent::log(array(
					'type'=>'activity',
					'column'=>array(
						'unique_target',
						'user_uid',
						'table_name',
						'action',
						'logged_at',
						'status',
						'login_id'
					),
					'value'=>array(
						$NewAsesmen,
						$UserData['data']->uid,
						'asesmen',
						'I',
						parent::format_date(),
						'N',
						$UserData['data']->log_id
					),
					'class'=>__CLASS__
				));

				$parameter['dataObj']['antrian'] = $parameter['dataAntrian']['uid'];
				$parameter['dataObj']['no_rm'] = $parameter['dataPasien']['no_rm'];
				$parameter['dataObj']['pasien'] = $parameter['dataAntrian']['uid_pasien'];
				$parameter['dataObj']['kunjungan'] = $parameter['dataAntrian']['kunjungan'];
				$parameter['dataObj']['departemen'] = $parameter['dataAntrian']['departemen'];

				$rawat = self::new_asesmen_rawat($parameter['dataObj'], $NewAsesmen, $PoliDetail['poli_asesmen']);

				$returnResponse = ["asesmen"=>$rawat,"asesmen_rawat"=>$rawat];
			} else {
				$returnResponse = $asesmen_poli;
			}
		}

		return $returnResponse;
	}

	private function get_antrian_asesmen_rawat(){
		$Authorization = new Authorization();
		$UserData = $Authorization::readBearerToken($parameter['access_token']);

		$listPoli = [];
		$getPoli = self::$query
			->select('master_poli_perawat', 
				array(
					'poli',
					'perawat'
				)
			)
			->where(array(
					'perawat' => '= ?',
					'AND',
					'deleted_at' => 'IS NULL'
				), array(
					$UserData['data']->uid
				)
			)
			->execute();

		foreach ($getPoli['response_data'] as $key => $value) {
			array_push($listPoli, $value);
		}

		$antrian = self::get_list_antrian($listPoli);

		$autonum = 1;
		foreach ($antrian as $key => $value) {
			$Poli = new Poli(self::$pdo);
			$PoliDetail = $Poli::get_poli_detail($value['uid_poli'])['response_data'][0];

			$cek_asesment = self::cek_asesmen_rawat_detail($PoliDetail['poli_asesmen'], $value['uid']);
			$antrian[$key]['status_asesmen'] = false;

			if ($cek_asesment['response_result'] > 0){
				$antrian[$key]['uid_asesmen_rawat'] = $cek_asesment['response_data'][0]['uid'];
				$antrian[$key]['status_asesmen'] = true; 
			}

			$antrian[$key]['autonum'] = $autonum;
			$autonum++;
		}

		return $antrian;
	}

	private function get_list_antrian($parameter){
		$antrian = new Antrian(self::$pdo);

		$listPasien = [];
		foreach ($parameter as $key => $value) {
			$antrianData = $antrian->get_antrian_by_poli($value['poli'])['response_data'];

			foreach ($antrianData as $key => $value) {				
				array_push($listPasien, $value);
			}
		}

		return $listPasien;
	}

	private function get_antrian_asesmen_medis(){
		$Authorization = new Authorization();
		$UserData = $Authorization::readBearerToken($parameter['access_token']);

		$antrianClass = new Antrian(self::$pdo);
		$antrian = $antrianClass->get_antrian_by_dokter($UserData['data']->uid);

		$antrianPasien = [];

		$autonum = 1;
		foreach ($antrian['response_data'] as $key => $value) {
			$Poli = new Poli(self::$pdo);
			$PoliDetail = $Poli::get_poli_detail($value['uid_poli'])['response_data'][0];

			$cek_asesment = self::cek_asesmen_medis_detail($PoliDetail['poli_asesmen'], $value['uid']);
			$antrian['response_data'][$key]['status_asesmen'] = false;

			if ($cek_asesment['response_result'] > 0){
				$antrian['response_data'][$key]['uid_asesmen_medis'] = $cek_asesment['response_data'][0]['uid'];
				$antrian['response_data'][$key]['status_asesmen'] = true; 
			}

			$antrian['response_data'][$key]['autonum'] = $autonum;
			$autonum++;
		}

		return $antrian;
	}

	/*private function get_list_antrian_medis($parameter){
		$antrian = new Antrian(self::$pdo);

		$listPasien = [];
		foreach ($parameter as $key => $value) {
			$antrianData = $antrian->get_antrian_by_dokter($value['poli'])['response_data'];

			foreach ($antrianData as $key => $value) {				
				array_push($listPasien, $value);
			}
		}

		return $listPasien;
	}*/

	private function cek_asesmen_rawat_detail($poli_prefix, $parameter){
		$data = self::$query
				->select('asesmen_rawat_' . $poli_prefix, array('uid','antrian'))
				->where(array(
							'deleted_at' => 'IS NULL',
							'AND',
							'antrian' => '= ?'
					),
					array($parameter)
				)
				->execute();

		return $data;
	}

	private function cek_asesmen_medis_detail($poli_prefix, $parameter){
		$data = self::$query
				->select('asesmen_medis_' . $poli_prefix, array('uid','antrian'))
				->where(array(
							'deleted_at' => 'IS NULL',
							'AND',
							'antrian' => '= ?'
					),
					array($parameter)
				)
				->execute();

		return $data;
	}

	private function new_asesmen_rawat($dataAsesmen, $uid_asesmen, $poli) {
		$Authorization = new Authorization();
		$UserData = $Authorization::readBearerToken($parameter['access_token']);
		$uid = parent::gen_uuid();

		$dataAsesmen['uid'] = $uid;
		$dataAsesmen['asesmen'] = $uid_asesmen;
		$dataAsesmen['perawat'] = $UserData['data']->uid;
		$dataAsesmen['waktu_pengkajian'] = parent::format_date();
		$dataAsesmen['created_at'] = parent::format_date();
		$dataAsesmen['updated_at'] = parent::format_date();

		$rawat = self::$query
			->insert('asesmen_rawat_' . $poli, $dataAsesmen)
			->execute();

		if($rawat['response_result'] > 0) {
			$log = parent::log(array(
				'type'=>'activity',
				'column'=>array(
					'unique_target',
					'user_uid',
					'table_name',
					'action',
					'new_value',
					'logged_at',
					'status',
					'login_id'
				),
				'value'=>array(
					$parent,
					$UserData['data']->uid,
					'asesmen_rawat_' . $poli,
					'I',
					json_encode($parameter),
					parent::format_date(),
					'N',
					$UserData['data']->log_id
				),
				'class'=>__CLASS__
			));

			$updateAsesmen = self::$query
				->update('asesmen', array('status' => 'D'))
				->where(array('asesmen.uid' => '= ?'), array($uid_asesmen))
				->execute();
		}

		return $rawat;
	}

	//function for combine function for get antrian data and pasien data detail
	private function get_pasien_asesmen_rawat($params){	
		$dataAntrian = self::get_data_antrian_detail($params);
		$dataPasien = self::get_data_pasien($dataAntrian['uid_pasien']);

		$penjamin = new Penjamin(self::$pdo);
		$param = ['','penjamin-detail', $dataAntrian['penjamin']];
		$get_penjamin = $penjamin->__GET__($param);
		$dataAntrian['nama_penjamin'] = $get_penjamin['response_data'][0]['nama'];

		$poli = new Poli(self::$pdo);
		$param = ['','poli-detail', $dataAntrian['departemen']];
		$get_poli = $poli->__GET__($param);
		$dataAntrian['nama_departemen'] = $get_poli['response_data'][0]['nama'];

		$result = array(
					"antrian"=>$dataAntrian,
					"pasien"=>$dataPasien
				);

		return $result;
	}

	//function for get antrian detail data
	private function get_data_antrian_detail($parameter){ //$parameter = uid antrian
		/*-------- GET DATA ANTRIAN ----------*/
		$antrian = new Antrian(self::$pdo);
		$param = ['','antrian-detail', $parameter];
		$get_antrian = $antrian->__GET__($param);
		$result = array(
					"uid"=>$get_antrian['response_data'][0]['uid'],
					"kunjungan"=>$get_antrian['response_data'][0]['kunjungan'],
					"uid_pasien"=>$get_antrian['response_data'][0]['pasien'],
					"departemen"=>$get_antrian['response_data'][0]['departemen'],
					"penjamin"=>$get_antrian['response_data'][0]['penjamin'],
					"dokter"=>$get_antrian['response_data'][0]['dokter'],
					"waktu_masuk"=>$get_antrian['response_data'][0]['waktu_masuk']
				);

		return $result;
	}

	//function for get pasien detail data
	private function get_data_pasien($parameter){		//$parameter = uid pasien
		/*--------- GET NO RM --------------- */
		$pasien = new Pasien(self::$pdo);
		$param = ['','pasien-detail', $parameter];
		$get_pasien = $pasien->__GET__($param);

		$term = new Terminologi(self::$pdo);
		$value = $get_pasien['response_data'][0]['jenkel'];
		$param = ['','terminologi-items-detail',$value];
		$get_jenkel = $term->__GET__($param);

		$value = $get_pasien['response_data'][0]['panggilan'];
		$param = ['','terminologi-items-detail',$value];
		$get_panggilan = $term->__GET__($param);

		$result = array(
					'uid'=>$get_pasien['response_data'][0]['uid'],
					'no_rm'=>$get_pasien['response_data'][0]['no_rm'],
					'nama'=>$get_pasien['response_data'][0]['nama'],
					'tanggal_lahir'=>$get_pasien['response_data'][0]['tanggal_lahir'],
					'jenkel'=>$get_jenkel['response_data'][0]['nama'],
					'id_jenkel'=>$get_pasien['response_data'][0]['jenkel'],
					'panggilan'=>$get_panggilan['response_data'][0]['nama']
				);

		return $result;
	}
}