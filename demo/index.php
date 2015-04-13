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
    <nav id="top">
      <img id="logoFR" src="img/logo.jpg" />
      <a href="" class="tban"> Plateforme nationale des ERP</a>
      <img id="logoIGN" src="img/logo_ign.gif" style="float:right;" />
    </nav>
    <div id="map_canvas"></div>
    <script src="js/leaflet.js"></script>
    <script src="js/jquery-1.11.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script type="text/javascript">
    
        function getAdresse() {
          q = $.trim($('#q').val());
          if(q.length > 4) {
            $.getJSON('http://api.adresse.data.gouv.fr/search/', { q: q, limit: 10 }, function (data) {
              if (data) {
                console.log(data);
              /*
                if (typeof mark == "object") { map.removeLayer(mark); }
                point = new L.LatLng(data.resultVarName.lat, data.resultVarName.lon);
                mark = new L.Marker(point);
                map.addLayer(mark);
                map.panTo(point);
                */
              }
            });
          }
        }
    
    
        var erpLayer;

        function getERP(e) {
          $.getJSON( "getData.php", { lat: e.latlng.lat, lng: e.latlng.lng }, function(data) {
            if(data !== 'no'){
              if(map.hasLayer(erpLayer) == true) {
                map.removeLayer(erpLayer);
              }
              if(typeof data.geojson !== 'undefined') {
                var geojsonFeature = {
                    "type": "Feature",
                    "properties": {
                      "popupContent": data.popup
                    },
                    "geometry": data.geojson
                };
                var myStyle = {
                  "color": "#ff7800",
                  "weight": 2,
                  "opacity": 1,
                  "fillOpacity": 0
                };
                erpLayer = new L.featureGroup();
                erpLayer.addLayer(new L.geoJson(geojsonFeature, {
                   style: myStyle,
                   onEachFeature: function (feature, layer) {
                     pop = layer.bindPopup(feature.properties.popupContent);
                   }
                }));
                erpLayer.addTo(map);
                pop.openPopup();
              }
            }
          });
        }
    
      function getWFS() {
        if(scaleDenominator < 4000) {
          console.log('launch WFS');
        }
      }
      
      function fitScreen() {
        if($(window).outerWidth() < 545) { 
          $('#logoFR').attr('src', 'img/logo_light.jpg');
          $('#logoIGN').attr('src', 'img/logo_ign_light.gif');
          $('.tban').toggleClass("tban tban2");
          $('#top').css('height', '37px');
        }
        else {
          $('#logoFR').attr('src', 'img/logo.jpg');
          $('#logoIGN').attr('src', 'img/logo_ign.gif');
          $('#top').css('height', '80px');
          $('.tban2').toggleClass("tban2 tban");
        }
        maph=$(window).height()-$('#top').outerHeight();
        $('#map_canvas').height(maph);
      }
    
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
        
        allLayers['orthophotos_ign'] = new Array(
          L.tileLayer('http://gpp3-wxs.ign.fr/'+ignkey+'/wmts?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=ORTHOIMAGERY.ORTHOPHOTOS&STYLE=normal&TILEMATRIXSET=PM&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}&FORMAT=image%2Fjpeg', {
            minZoom: 1,
            maxZoom: 19,
            fn: getIgnAttributions,
            name: 'Photographies aériennes',
            layername: 'ORTHOIMAGERY.ORTHOPHOTOS',
            attribution: '© <a href="http://www.geoportail.gouv.fr/">Géoportail</a>'
          }), '', 2);
          
        allLayers['relief_ign'] = new Array(
          L.tileLayer('http://gpp3-wxs.ign.fr/'+ignkey+'/wmts?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=ELEVATION.SLOPES&STYLE=normal&TILEMATRIXSET=PM&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}&FORMAT=image%2Fjpeg', {
            minZoom: 1,
            maxZoom: 19,
            fn: getIgnAttributions,
            name: 'Carte du relief',
            layername: 'ELEVATION.SLOPES',
            attribution: '© <a href="http://www.geoportail.gouv.fr/">Géoportail</a>'
          }), '', 2);
        
        allLayers['erp32'] = new Array(
          L.tileLayer("http://www.ideeslibres.org/mapproxy/tiles/erp32_EPSG900913/{z}/{x}/{y}.png", {
            tms: true,
            opacity: 0.6,
            maxZoom: 19,
            minZoom: 1,
            name: 'ERP du Gers (32)',
            layername: 'erp32',
            attribution: '<a href="http://catalogue.geo-ide.developpement-durable.gouv.fr/catalogue/apps/search/?uuid=fr-120066022-jdd-5fedeff4-9782-4d93-b0d4-063b2cc55c7d" title="Établissements recevant du public dans le Gers">DDT du Gers</a>'
          }), '', 20);
        
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
        
        allLayers['risques32'] = new Array(
          L.tileLayer("http://www.ideeslibres.org/mapproxy/tiles/risques32_EPSG900913/{z}/{x}/{y}.png", {
            tms: true,
            opacity: 0.6,
            maxZoom: 18,
            minZoom: 1,
            name: 'Risques Gers (32)',
            layername: 'risques32',
            attribution: '<a href="http://cartorisque.prim.net/dpt/32/32_ip.html" title="Risques dans le Gers">Cartorisque</a>'
          }), '', 20);
          
        allLayers['noms_ign'] = new Array(
          L.tileLayer('http://gpp3-wxs.ign.fr/'+ignkey+'/wmts?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=GEOGRAPHICALNAMES.NAMES&STYLE=normal&TILEMATRIXSET=PM&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}&FORMAT=image%2Fpng', {
            minZoom: 1,
            maxZoom: 19,
            opacity: 0.6,
            fn: getIgnAttributions,
            name: 'Dénominations géographiques',
            layername: 'GEOGRAPHICALNAMES.NAMES',
            attribution: '© <a href="http://www.geoportail.gouv.fr/">Géoportail</a>'
          }), '', 2);
        
        map = L.map('map_canvas', {
          center: [47.06129129529406, 4.655869150706053],
          zoom: 6,
          zoomControl: false,
          attributionControl : false,
          layers: [allLayers['orthophotos_ign'][0], allLayers['rail_ign'][0], allLayers['routes_ign'][0], allLayers['erp32'][0], allLayers['noms_ign'][0]]
        });
        /*
        allLayers['orthophotos_ign'][0].addTo(map);
        allLayers['population'][0].addTo(map);
        allLayers['rail_ign'][0].addTo(map);
        allLayers['routes_ign'][0].addTo(map);
        */
        
        var baseMaps = {
          
        };

        var overlayMaps = {
          "Photographies aériennes": allLayers['orthophotos_ign'][0],
          "Relief": allLayers['relief_ign'][0],
          "Population": allLayers['population'][0],
          "Réseaux routiers": allLayers['routes_ign'][0],
          "Réseaux ferrés": allLayers['rail_ign'][0],
          "Risques - Gers (32)": allLayers['risques32'][0],
          "ERP - Gers (32)": allLayers['erp32'][0],
          "Dénominations géographiques": allLayers['noms_ign'][0],
        };
        
        L.control.layers(baseMaps, overlayMaps).addTo(map);
        
        cattribution = new L.control.attribution({"position": 'bottomleft', prefix: false}).addTo(map);
        
        var dscale = L.control({position: 'bottomright'});
        dscale.onAdd = function (map) {
          var div = L.DomUtil.create('div', 'dscale');
          div.innerHTML = 'Échelle 1:'+getScaleDenominator();
          return div;
        };
        dscale.addTo(map);
        
        var searchbox = L.control({position: 'topleft'});
        searchbox.onAdd = function (map) {
          var div = L.DomUtil.create('div', 'q-container');
          div.innerHTML = '<form name="search" id="search"><input type="text" name="q" id="q" /><input type="submit" value="ok" />';
          return div;
        };
        //searchbox.addTo(map);
        
        cscale = L.control.scale({"position": 'bottomright', "imperial": false, "updateWhenIdle": true}).addTo(map);
        czoom = L.control.zoom({"position": 'bottomright'}).addTo(map);
        
        $("#search").submit(function( event ) {
          event.preventDefault();
          getAdresse();
        });
        
        map.on('layeradd', function(e) {
          if (e.layer.options) {
            if (e.layer.options.fn) {
              e.layer.on('loading', function(e) {
                this.options.fn(e);
              });
            }
          }
        });
          
        map.on('layerremove', function(e) {
          if (e.layer.options) {
            if (e.layer.options.fn) {
              e.layer.off('load', function(e) {
                this.options.fn(e);
              });
            }
          }
        });
          
        map.on('moveend', function(e) {
          setScaleDenominator();
          setHashLink();
        });
        
        map.on('click', getERP);
    
        stopPropag();
        loadFromHash();
      }
      
      function loadFromHash() {
        if(location.hash) {
          hashvars = new Array();
          hashes = location.hash.substring(1).split('&');
          for (vars in hashes) {
            v = hashes[vars].split('=');
            hashvars[v[0]] = v[1];
          }
          if(hashvars.hasOwnProperty('bbox')) {
            b = hashvars['bbox'].split(',');
            var bounds = [[b[0], b[1]], [b[2], b[3]]];
            map.fitBounds(bounds);
          }
        }
      }

      function setHashLink() {
        box = map.getBounds();
        hashes = 'bbox='+box._southWest.lat+','+box._southWest.lng+','+box._northEast.lat+','+box._northEast.lng;
        window.location.hash = hashes;
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
        $('.dscale').html('Échelle 1:'+getScaleDenominator());
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
      
      $(document).ready( function() { 
        initmap(); 
        $(window).resize(function() {
          fitScreen();
        });
        fitScreen();
      });
      
    </script>
  <script type="text/javascript">
  </script>
  </body>
</html>
