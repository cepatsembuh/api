    <?php
     
    $method = $_SERVER['REQUEST_METHOD'];
    $request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
    $input = json_decode(file_get_contents('php://input'),true);
     
    $link = mysqli_connect('0.0.0.0', 'donyriyanto', '', 'c9');
    mysqli_set_charset($link,'utf8');
     
    $data = preg_replace('/[^a-z0-9_]+/i','',array_shift($request));
    $id = array_shift($request)+0;
    if (strlen($data) > 0) {
     if (!mysqli_query($link,"DESCRIBE `$data`")){
      switch (strtoupper($data)) {
      case 'CEKPOLI' :
       $sql = "SELECT jadwaldokter.jadwaldokter_id, 
                     poliklinik_nama AS poliklinik, 
                     dokter_nama AS dokter, 
                     DATE_FORMAT( jadwaldokter_tanggalpraktek, '%c %M %Y' ) AS tanggalpraktek, 
                     TIME_FORMAT( jadwaldokter_jammulaipraktek, '%H:%i' ) AS jammulai, 
                     TIME_FORMAT( jadwaldokter_jamselesaipraktek, '%H:%i' ) AS jamselesai
              FROM jadwaldokter
              LEFT JOIN dokter ON jadwaldokter.dokter_NID = dokter.dokter_NID
              WHERE faskes_kode = '".$id."' ";
              
       $tglpraktek = array_shift($request);
       if (isset($tglpraktek)) {
        $sql.="AND jadwaldokter_tanggalpraktek = '".$tglpraktek."'
              LIMIT 0 , 30";
       } else {
        $sql.="AND jadwaldokter_tanggalpraktek = CURDATE()
              LIMIT 0 , 30";
       }
       
       //print $sql;
       $result = mysqli_query($link,$sql);
       
       if (!$result) {
        http_response_code(404);
        die(mysqli_error($link));
       } else {
        $hasil=array();
        while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
         $hasil[]=$row;
        } 
        echo json_encode($hasil);
       }
     
       die();
       break;
      case 'DAFTAR':
       $idjadwal = $id;
       if (isset($idjadwal)) {
        $nomorpasien = array_shift($request);
        //Prioritas pencarian nomor pasien dulu
        $sql = "SELECT rekammedis_id FROM pasien LEFT JOIN rekammedis ON rekammedis.pasien_id = pasien.pasien_id 
                WHERE pasien_BPJS='".$nomorpasien."'
                   OR pasien_NIK='".$nomorpasien."'
                ORDER BY pasien_BPJS 
                LIMIT 1";
        $result = mysqli_query($link,$sql);
        if (mysqli_num_rows($result)==0) {
         http_response_code(404);
         die('Data pasien tidak ditemukan');
        } else {
         $row = mysqli_fetch_assoc($result);
         $idrekammedis = $row['rekammedis_id'];
         $sql = "SELECT faskes_kode, poliklinik_nama FROM jadwaldokter 
                 WHERE jadwaldokter_id=".$idjadwal;
         $result = mysqli_query($link,$sql);
         if (mysqli_num_rows($result)==0) {
          http_response_code(404);
          die('Data pasien tidak ditemukan');
         } else {
          $row = mysqli_fetch_assoc($result);
          $kodefaskes = $row['faskes_kode'];
          $namapoli = $row['poliklinik_nama'];
          
          $sql = "SELECT antrian_nomor FROM antrian 
                 WHERE jadwaldokter_id=".$idjadwal." 
                   AND faskes_kode='".$kodefaskes."'
                   AND poliklinik_nama='".$namapoli."'
                   AND rekammedis_id=".$idrekammedis;
          $result = mysqli_query($link,$sql);
          if (mysqli_num_rows($result)==0) {
           $sql = "SELECT max(antrian_nomor)+1 FROM antrian 
                  WHERE jadwaldokter_id=".$idjadwal." 
                    AND faskes_kode='".$kodefaskes."'
                    AND poliklinik_nama='".$namapoli."' ";
           $result = mysqli_query($link,$sql);
           $row = mysqli_fetch_row($result);
           $noantrian = $row[0];
           if (isset($noantrian)) {
            $sql = "INSERT INTO antrian (`faskes_kode`, `poliklinik_nama`, `rekammedis_id`, `jadwaldokter_id`, `antrian_nomor`) 
                          VALUES ('".$kodefaskes."','".$namapoli."',".$idrekammedis.",".$idjadwal.",".$noantrian.")";
           } else {
            $sql = "INSERT INTO antrian (`faskes_kode`, `poliklinik_nama`, `rekammedis_id`, `jadwaldokter_id`, `antrian_nomor`)
                          VALUES ('".$kodefaskes."','".$namapoli."',".$idrekammedis.",".$idjadwal.",1)";
           }
           $result = mysqli_query($link,$sql);
           $sql = "SELECT antrian_nomor,
                          pasien_nama,
                          rekammedis_nomor,
                          faskes_nama,
                          antrian.poliklinik_nama,
                          jadwaldokter_tanggalpraktek
                   FROM antrian  
                     LEFT JOIN jadwaldokter ON jadwaldokter.jadwaldokter_id=antrian.jadwaldokter_id
                     LEFT JOIN dokter ON jadwaldokter.dokter_NID = dokter.dokter_NID
                     LEFT JOIN faskes ON jadwaldokter.faskes_kode = faskes.faskes_kode
                     LEFT JOIN rekammedis ON rekammedis.rekammedis_id = antrian.rekammedis_id
                     LEFT JOIN pasien ON pasien.pasien_id = rekammedis.pasien_id
                   WHERE jadwaldokter.jadwaldokter_id=".$idjadwal." 
                     AND faskes.faskes_kode='".$kodefaskes."'
                     AND antrian.poliklinik_nama='".$namapoli."'
                     AND rekammedis.rekammedis_id=".$idrekammedis;
           $result = mysqli_query($link,$sql);
           $row = mysqli_fetch_assoc($result);
           $noantrian = $row['antrian_nomor'];
           echo json_encode($row);
           
          } else {
           $row = mysqli_fetch_assoc($result);
           $noantrian = $row['antrian_nomor'];
           
           $sql = "SELECT antrian_nomor,
                          pasien_nama,
                          rekammedis_nomor,
                          faskes_nama,
                          antrian.poliklinik_nama,
                          jadwaldokter_tanggalpraktek
                   FROM antrian  
                     LEFT JOIN jadwaldokter ON jadwaldokter.jadwaldokter_id=antrian.jadwaldokter_id
                     LEFT JOIN dokter ON jadwaldokter.dokter_NID = dokter.dokter_NID
                     LEFT JOIN faskes ON jadwaldokter.faskes_kode = faskes.faskes_kode
                     LEFT JOIN rekammedis ON rekammedis.rekammedis_id = antrian.rekammedis_id
                     LEFT JOIN pasien ON pasien.pasien_id = rekammedis.pasien_id
                   WHERE jadwaldokter.jadwaldokter_id=".$idjadwal." 
                     AND faskes.faskes_kode='".$kodefaskes."'
                     AND antrian.antrian_nomor=".$noantrian;
           $result = mysqli_query($link,$sql);
           $row = mysqli_fetch_assoc($result);
           echo json_encode($row);
          }
         }
        }
       } else {
        die('Id jadwal tidak ditemukan '.$idjadwal); 
       }
       die();
       break;
      default:
       http_response_code(404);
       die('Command not found');
      }
     }
     
     switch ($method) {
     case 'GET':
     $sql = "select * from `$data`".($id?" WHERE ".$data."_id=$id":''); break;
     }
     $result = mysqli_query($link,$sql);
     
     if (!$result) {
     http_response_code(404);
     die(mysqli_error());
     }
     
     if ($method == 'GET') {
     $hasil=array();
     while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
     {
     $hasil[]=$row;
     } 
     //$hasil1 = array('status' => true, 'message' => 'Data show succes', 'data' => $hasil);
     echo json_encode($hasil);
     
     } elseif ($method == 'POST') {
     echo mysqli_insert_id($link);
     } else {
     echo mysqli_affected_rows($link);
     }
    }else{
     $hasil1 = array('status' => false, 'message' => 'Access Denied');
     echo json_encode($hasil1);
    }
    mysqli_close($link);
    ?>