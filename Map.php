<!DOCTYPE html>
<html>
    <head>
        <style>
            #map {
                width:100%;
                height:400px;
                background-color: grey;
            }
        </style>
    </head>
    <body>
        <h3>Google Maps Demo</h3>

        <div id="map"></div>
            <script>
                function initMap()
                {
                    var mapDiv = document.getElementById('map');
                    var map = new google.maps.Map(mapDiv, {
                        center : {lat: 44.450, lng: -78.546},
                        zoom : 8
                        }
                    )
                }
            </script>
            <script async defer src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyCBHS5iSlZuLpuSZmIXVLuwsqnieIPKNyE&callback=initMap"></script>

    </body>
</html>

<?php
?>