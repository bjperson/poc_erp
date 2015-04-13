<?php
header('Content-type: application/json; charset=utf8');
if(isset($_GET['lat']) && isset($_GET['lng'])) {
  $lat = htmlentities($_GET['lat']);
  $lng = htmlentities($_GET['lng']);
}
if(is_numeric($lat) && is_numeric($lng)) {

  require_once '../../../connect.php';

  $data['popup'] = '';

  $req = $bdd->prepare('SELECT ST_AsGeoJSON(wkt) as the_geom, n_sdis, nom_erp, insee, lien, categorie, type, n__voie, adresse_1, nature, diag_acces, trav_acces, hebergement, nom_prop, capacite, nb_couch, type_chauf, clim, wc, lavabo, douche, restaurat, fermeture, proximite, coordonnee_resp, remarques FROM "erp32" WHERE st_contains(ST_SetSRID(wkt, 4326), ST_SetSRID(st_makepoint(:lng, :lat), 4326))');
  $req->execute(array('lng' => $lng, 'lat' => $lat));

  while ($donnees = $req->fetch())
  {
    $data['popup'] .= '<h1 class="popuptitle">'.$donnees['nom_erp'].'</h1>';
    $data['popup'] .= '<p style="font-size: 1.2em; color:gray;">Catégorie '.$donnees['categorie'].', Type '.$donnees['type'].'<br />';
    if($donnees['capacite'] !== 0) { 
      $data['popup'] .= 'Capacité : '.$donnees['capacite'].' personnes';
    }
    if($donnees['nb_couch'] !== 0) { 
      $data['popup'] .= ', Couchages : '.$donnees['nb_couch'];
    }
    $data['popup'] .= '</p>';
    $data['geojson'] = json_decode($donnees['the_geom']);
  }

  $req->closeCursor();
  if ($data['popup'] !== '') {
   echo json_encode($data);
  }
  else { echo 'no'; }
}
