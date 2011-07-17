var geocoder = new google.maps.Geocoder();


function geoEncode(address)
{
    geocoder.geocode( { 'address': address}, function(results, status) {
          if (status == google.maps.GeocoderStatus.OK) {
            //map.setCenter(results[0].geometry.location);
            //var marker = new google.maps.Marker({
            //    map: map, 
            //    position: results[0].geometry.location
            //});
            savePoint(results[0].geometry.location);
          } else {
            alert("A geokódolás sikertelen a következő okból: " + status);
          }
    });
}


function savePoint(geocode)
{
    jQuery("#esemeny-google-terkep-lng").val(geocode.lng());
    jQuery("#esemeny-google-terkep-lat").val(geocode.lat());
    
}



function terkepBetolt()
{
    var myLatlng = new google.maps.LatLng(document.getElementById("terkep-lat").value, document.getElementById("terkep-lng").value);
    var myOptions = {
      zoom: parseInt(document.getElementById("terkep-nagyitas").value),
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    var map = new google.maps.Map(document.getElementById("terkep"), myOptions);
    
    var obj = new Object();
    obj.map = map;
    obj.position = myLatlng;
    
    if( document.getElementById("terkep-mutatofelirat").value != "nincs" )
    {
        obj.title = document.getElementById("terkep-mutatofelirat").value;
    }
    
    var marker = new google.maps.Marker(obj);
    
    if( document.getElementById("terkep-infobuborek").value != "nincs" )
    {
        var infowindow = new google.maps.InfoWindow({
            maxWidth: 200,
            content: document.getElementById("terkep-infobuborek").value
        });
        infowindow.open(map, marker);
    }
}