<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ERP version alpha - IGN</title>
    <link href="css/leaflet.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/app.css" rel="stylesheet">
    <!--[if lt IE 9]>
    <script src="js/html5shiv.min.js"></script>
    <script src="js/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <div id="map_canvas"></div>
    <script src="js/leaflet.js"></script>
    <script src="js/jquery-1.11.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script type="text/javascript">
    
      function setInputText(id, text) {
        $(id).val(text);
        $(id).on({
          focus: function(){
            if($(this).val() === text) {
              $(this).val('');
            }
          },
          blur: function(){
            if($(this).val() === '') {
              $(this).val(text);
            }
          }
        });
      }
      
      function initmap() {
        
        map = L.map('map_canvas', {
          center: [47.06129129529406, 4.655869150706053],
          zoom: 6,
          zoomControl: false,
          attributionControl : false
        });
        
        cattribution = new L.control.attribution({"position": 'bottomleft', prefix: false}).addTo(map);
        
        var dscale = L.control({position: 'bottomright'});
        dscale.onAdd = function (map) {
          var div = L.DomUtil.create('div', 'dscale');
          div.innerHTML = 'Echelle 1:'+getScaleDenominator();
          return div;
        };
        dscale.addTo(map);
        
        cscale = L.control.scale({"position": 'bottomright', "imperial": false, "updateWhenIdle": true}).addTo(map);
        czoom = L.control.zoom({"position": 'bottomright'}).addTo(map);
        
        allLayers['orthophotos_ign'] = new Array(
          L.tileLayer('http://gpp3-wxs.ign.fr/'+ignkey+'/wmts?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=ORTHOIMAGERY.ORTHOPHOTOS&STYLE=normal&TILEMATRIXSET=PM&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}&FORMAT=image%2Fjpeg', {
            minZoom: 1,
            maxZoom: 19,
            fn: getIgnAttributions,
            name: 'Photographies aériennes',
            layername: 'ORTHOIMAGERY.ORTHOPHOTOS',
            attribution: '© <a href="http://www.geoportail.gouv.fr/">Géoportail</a>'
          }), '', 2);
        
        allLayers['rail_ign'] = new Array(
          L.tileLayer('http://gpp3-wxs.ign.fr/'+ignkey+'/wmts?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=TRANSPORTNETWORKS.RAILWAYS&STYLE=normal&TILEMATRIXSET=PM&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}&FORMAT=image%2Fpng', {
            minZoom: 1,
            maxZoom: 19,
            opacity: 0.6,
            fn: getIgnAttributions,
            name: 'Réseaux de transports (Ferré)',
            layername: 'TRANSPORTNETWORKS.RAILWAYS',
            attribution: '© <a href="http://www.geoportail.gouv.fr/">Géoportail</a>'
          }), '', 2);
    
        allLayers['routes_ign'] = new Array(
          L.tileLayer('http://gpp3-wxs.ign.fr/'+ignkey+'/wmts?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=TRANSPORTNETWORKS.ROADS&STYLE=normal&TILEMATRIXSET=PM&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}&FORMAT=image%2Fpng', {
            minZoom: 1,
            maxZoom: 19,
            opacity: 0.6,
            fn: getIgnAttributions,
            name: 'Réseaux de transports (Routier)',
            layername: 'TRANSPORTNETWORKS.ROADS',
            attribution: '© <a href="http://www.geoportail.gouv.fr/">Géoportail</a>'
          }), '', 2);
          
        allLayers['population'] = new Array(
          L.tileLayer("http://www.ideeslibres.org/mapproxy/tiles/carroyage_pop_EPSG900913/{z}/{x}/{y}.png", {
            tms: true,
            opacity: 0.6,
            maxZoom: 16,
            minZoom: 1,
            name: 'Population (carroyage)',
            layername: 'population',
            attribution: '<a href="http://www.insee.fr/fr/themes/detail.asp?reg_id=0&ref_id=donnees-carroyees&page=donnees-detaillees/donnees-carroyees/donnees_carroyees_diffusion.htm" title="Population : Données carroyées INSEE">INSEE</a>'
          }), '', 20);
          
        map.on('layeradd', function(e) {
          if (e.layer.options.fn) {
            e.layer.on('loading', function(e) {
              this.options.fn(e);
            });
          }
        });
          
        map.on('layerremove', function(e) {
          if (e.layer.options.fn) {
            e.layer.off('loading', function(e) {
              this.options.fn(e);
            });
          }
        });
          
        map.on('viewreset', function(e) {
          setScaleDenominator();
        });
        
        allLayers['orthophotos_ign'][0].addTo(map);
        allLayers['population'][0].addTo(map);
        allLayers['rail_ign'][0].addTo(map);
        allLayers['routes_ign'][0].addTo(map);
    
        stopPropag();
      }
    
      function stopPropag() {
        $.each($('.leaflet-control'), function() {
          L.DomEvent.disableClickPropagation(this);
          L.DomEvent.on(this, 'click', L.DomEvent.stopPropagation);
          L.DomEvent.on(this, 'mousewheel', L.DomEvent.stopPropagation);
          L.DomEvent.on(this, 'MozMousePixelScroll', L.DomEvent.stopPropagation);
        });
      }
      
      var scaleDenominator;
      function getScaleDenominator() {
        /*
        According to OGC SLD 1.0 specification: The "standardized rendering pixel size" is defined to be 0.28mm Ã— 0.28mm (millimeters).
        1 pixel = 0.00028m. To calculate the ScaleDenominator for the map on a computer screen, you need to divide the horizontal or vertical
        real world pixel size by the display pixel size: ScaleDenominator = 2m / 0.00028m = 7142.8571. Therefore, the scale of the displayed map
        is 1:7142.8571.
        */
        bounds = map.getBounds();
        southEast = L.latLng(bounds.getSouthWest().lat,bounds.getNorthEast().lng);
        realworldm = southEast.distanceTo(bounds.getSouthWest());
        size = map.getSize();
        realworldpx = realworldm / size.x;
        scaleDenominator = Math.round(realworldpx / 0.00028);
        return scaleDenominator;
      }
      
      function setScaleDenominator() {
        $('.dscale').html('Echelle 1:'+getScaleDenominator());
      }

      ignattributions = false;
      var igndata;
      fix = false;

      function fixSelector(target) {
        if(fix) {
          reg= new RegExp('[^:]+:');
          target = target.replace(reg,'');
        }
        return target;
      }

      function getIgnAttributions(e) {
        // ignattributions déjà chargé
        if(ignattributions) {
          if(ignattributions.timestamp+(1000 * 60 * 60 * 24) > $.now()) { loadnew = false; } else { loadnew = true; }
        }
        // pas chargé
        else {
          // mais déjà stocké
          if (localStorage['ignattributions'] !== undefined) {
            ignattributions = jQuery.parseJSON(localStorage['ignattributions']);
            // si pas plus vieux qu'un jour
            if(ignattributions.timestamp+(1000 * 60 * 60 * 24) > $.now()) {
              loadnew = false;
            // si trop vieux
            } else { loadnew = true; }
          }
          // si pas stocké
          else { loadnew = true; }
        }

        if(loadnew) {
          console.log("Téléchargement de l'autoconf");
          $.getJSON('http://gpp3-wxs.ign.fr/'+ignkey+'/autoconf/?output=json&callback=?',
            function(data){
              if(data.http.status == 200) {
                igndata = $( $.parseXML( data.xml ) );
                ignattributions = {'timestamp':$.now(),'rules':new Array()};

                igndata.find( "Layer" ).each(function() {
                  nom = new Array();
                  $(this).find("Name").each(function() {
                    nom.push($(this).text());
                  });

                  // Fix chrome
                  if($(this).find("gpp\\:Originator").length !== 0) { fix = false; } else { fix = true; }

                  $(this).find(fixSelector("gpp\\:Originator")).each(function() {
                    name = $(this).attr('name');
                    title = $(this).find(fixSelector("gpp\\:Attribution")).text();
                    url = $(this).find(fixSelector("gpp\\:URL")).text();
                    bboxs = new Array();
                    $(this).find(fixSelector("gpp\\:BoundingBox")).each(function() {
                      bboxs.push($(this).text());
                    });
                    minscale = new Array();
                    $(this).find(fixSelector("sld\\:MinScaleDenominator")).each(function() {
                      minscale.push($(this).text());
                    });
                    maxscale = new Array();
                    $(this).find(fixSelector("sld\\:MaxScaleDenominator")).each(function() {
                      maxscale.push($(this).text());
                    });
                    crs = new Array();
                    $(this).find(fixSelector("gpp\\:CRS")).each(function() {
                      crs.push($(this).text());
                    });
                    for (bbox in bboxs) {
                      b = bboxs[bbox].split(',');
                      bounds = [[b[1], b[0]], [b[3], b[2]]];
                      line = {'layer':nom[0],'nom':name,'title':title,'url':url,'crs':crs[bbox],'bbox':bounds,'minscale':minscale[bbox],'maxscale':maxscale[bbox]};
                      ignattributions.rules.push(line);
                    }
                  });
                });
                localStorage['ignattributions'] = JSON.stringify(ignattributions);
              }
            });
        }

        currentscale = getScaleDenominator();
        //console.log('Date : '+ignattributions.timestamp+' ; ScaleDenominator : '+currentscale);
        //console.log(this.layername);
        
        newAttribution = new Array();
        for (i in ignattributions.rules) {
        
          attribution = '<a href="'+ignattributions.rules[i].url+'" title="'+this.name+' : '+ignattributions.rules[i].title+'" class="'+ignattributions.rules[i].layer+'">'+ignattributions.rules[i].nom+'</a>';
          
          if(ignattributions.rules[i].layer == this.layername && currentscale >= ignattributions.rules[i].minscale && currentscale <= ignattributions.rules[i].maxscale) {
            if(map.getBounds().intersects(ignattributions.rules[i].bbox)) {
              if($.inArray(attribution, newAttribution) === -1) {
                newAttribution.push(attribution);
              }
              //console.log('Plus '+ignattributions.rules[i].nom+' : '+ignattributions.rules[i].minscale+' <> '+ignattributions.rules[i].maxscale);
            }
          }
          else if(ignattributions.rules[i].layer == this.layername && (currentscale < ignattributions.rules[i].minscale || currentscale > ignattributions.rules[i].maxscale)) {
            cattribution.removeAttribution(attribution);
            //console.log('Moins '+ignattributions.rules[i].nom+' : '+ignattributions.rules[i].minscale+' <> '+ignattributions.rules[i].maxscale);
          }
          
          if(e.type == 'layerremove' && ignattributions.rules[i].layer == this.layername) {
            // tester effectivité
            cattribution.removeAttribution(attribution);
          }
        }
        // ajouter toutes les attributions
        for (i in newAttribution) {
          cattribution.addAttribution(newAttribution[i]);
        }
      }
      
      allLayers = []; var config; var datas = [];
      
      ignkey = 'ul6js63hun6vaxxeso802ru5';
      
      $(document).ready( function() { initmap(); });
      
    </script>
  <script type="text/javascript">
  </script>
  </body>
</html>
