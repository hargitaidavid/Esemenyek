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