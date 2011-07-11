var $j = jQuery.noConflict();

$j(function()
{
    AnyTime.picker( "idopont-kezdo",
        { format: "%Z-%m-%d %H:%i:00", firstDOW: 1 } );
    AnyTime.picker( "idopont-befejezo",
        { format: "%Z-%m-%d %H:%i:00", firstDOW: 1 } );
    
    if($j("#terkep-adatok").length)
    {
        function terkepAdatokFrissitese()
        {
            var cim = '';
            if($j("#esemeny-helyszin-iranyitoszam").val() != '')
            {
                cim += $j("#esemeny-helyszin-iranyitoszam").val() + ' ';
            }
            if($j("#esemeny-helyszin-varos").val() != '')
            {
                cim += $j("#esemeny-helyszin-varos").val() + ' ';
            }
            if($j("#esemeny-helyszin-cim").val() != '')
            {
                cim += $j("#esemeny-helyszin-cim").val();
            }

            geoEncode(cim);
        }

        if(!$j("#terkep-adatok").is(':checked'))
        {
            $j("#terkep-adatok").hide();
        }
        
        $j("#esemeny-google-terkep").click(function(){
            
            if($j("#esemeny-google-terkep").attr('checked'))
            {
                $j("#terkep-adatok").slideDown('normal');
                terkepAdatokFrissitese();
            }
            else
            {
                $j("#terkep-adatok").slideUp('normal');
                $j("#esemeny-google-terkep-lng, #esemeny-google-terkep-lat").val('');
            }
        });
        
        $j("#terkepBetolto").click(terkepAdatokFrissitese);
    }
    
    if($j("#terkep").length)
    {
        var myLatlng = new google.maps.LatLng(46.430411,20.31883);
        var myOptions = {
          zoom: parseInt($j("#google_terkep_nagyitas").val()),
          center: myLatlng,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        var map = new google.maps.Map(document.getElementById("terkep"), myOptions);
        
        var marker = new google.maps.Marker({
            map: map, 
            position: myLatlng,
            title: "Ez a mutató felirata"
        });
        
        var infowindow = new google.maps.InfoWindow({
            maxWidth: 200,
            content: "<h1>Infó buborék</h1><p>Valahogy <strong>így</strong> néz ki a szöveg benne, amit beleírunk <em>a szerkesztőbe</em>.</p>"
        });
        infowindow.open(map,marker);
    }
});