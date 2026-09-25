(function () {
    function initMap() {
        const mapElement = document.getElementById('antipolo-map');
        const pointAEl = document.getElementById('point_a');
        const pointBEl = document.getElementById('point_b');
        const distanceEl = document.getElementById('distance');
        const statusEl = document.getElementById('map-status');
        const searchInput = document.getElementById('map-search-input');
        const searchButton = document.getElementById('map-search-button');
        const searchResults = document.getElementById('map-search-results');
        const selectionStatus = document.getElementById('map-selection-status');
        const form = document.getElementById('fare-form');

        if (!mapElement || !pointAEl || !pointBEl || !distanceEl || typeof L === 'undefined') {
            return;
        }

        // Keep the viewport focused on lower-to-upper Antipolo.
        const antipoloBounds = L.latLngBounds(
            [14.535, 121.080], // southwest
            [14.680, 121.230]  // northeast
        );
        const map = L.map(mapElement, {
            maxBounds: antipoloBounds,
            maxBoundsViscosity: 1.0,
            minZoom: 12,
        });
        map.fitBounds(antipoloBounds, { padding: [12, 12] });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 18,
        }).addTo(map);

        const markers = {};
        let routeLine = null;
        let selectedA = null;
        let selectedB = null;

        Array.from(pointAEl.options).forEach(function (option) {
            const lat = parseFloat(option.dataset.lat);
            const lng = parseFloat(option.dataset.lng);
            markers[option.value] = L.marker([lat, lng])
                .addTo(map)
                .bindPopup(option.textContent.trim())
                .on('click', function () {
                    choosePoint({
                        id: option.value,
                        name: option.textContent.trim(),
                        lat: lat,
                        lng: lng,
                    });
                });
        });

        function selectedPoint(select) {
            return select.selectedOptions[0];
        }

        function addPointOption(point, slot) {
            const select = slot === 'a' ? pointAEl : pointBEl;
            const value = 'searched_' + slot;
            let option = Array.from(select.options).find(function (item) {
                return item.value === value;
            });
            if (!option) {
                option = new Option(point.name, value);
                select.add(option);
            }
            option.textContent = point.name;
            option.dataset.lat = point.lat;
            option.dataset.lng = point.lng;
            select.value = value;
            select.dispatchEvent(new Event('change'));
            form.querySelector('[name="point_' + slot + '_lat"]')?.remove();
            form.querySelector('[name="point_' + slot + '_lng"]')?.remove();
            ['lat', 'lng'].forEach(function (coordinate) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'point_' + slot + '_' + coordinate;
                hidden.value = point[coordinate];
                form.appendChild(hidden);
            });
        }

        function choosePoint(point) {
            if (!selectedA || selectedB) {
                selectedA = point;
                selectedB = null;
                addPointOption(point, 'a');
                selectionStatus.textContent = 'From set to ' + point.name + '. Choose the second place for To.';
            } else {
                selectedB = point;
                addPointOption(point, 'b');
                selectionStatus.textContent = 'To set to ' + point.name + '. Search or click again to start a new From/To pair.';
            }
            updateRoute();
        }

        async function searchPlaces() {
            const query = searchInput.value.trim();
            if (!query) return;
            searchResults.textContent = 'Searching...';
            const params = new URLSearchParams({
                q: query + ', Antipolo, Rizal, Philippines',
                format: 'jsonv2',
                limit: '6',
                viewbox: '121.080,14.680,121.230,14.535',
                bounded: '1',
            });
            try {
                const response = await fetch('https://nominatim.openstreetmap.org/search?' + params);
                if (!response.ok) throw new Error('Place search is unavailable.');
                const places = await response.json();
                searchResults.textContent = '';
                if (!places.length) {
                    searchResults.textContent = 'No Antipolo places found.';
                    return;
                }
                places.forEach(function (place) {
                    const result = document.createElement('button');
                    result.type = 'button';
                    result.className = 'map-search-result';
                    result.textContent = place.display_name;
                    result.addEventListener('click', function () {
                        const point = {
                            id: 'search_' + place.place_id,
                            name: place.display_name,
                            lat: parseFloat(place.lat),
                            lng: parseFloat(place.lon),
                        };
                        L.marker([point.lat, point.lng]).addTo(map).bindPopup(point.name).openPopup();
                        map.setView([point.lat, point.lng], 15);
                        choosePoint(point);
                        searchResults.textContent = '';
                    });
                    searchResults.appendChild(result);
                });
            } catch (error) {
                searchResults.textContent = error.message;
            }
        }

        async function updateRoute() {
            const pointA = selectedPoint(pointAEl);
            const pointB = selectedPoint(pointBEl);
            if (!pointA || !pointB) return;

            if (routeLine) {
                map.removeLayer(routeLine);
                routeLine = null;
            }

            if (pointA.value === pointB.value) {
                distanceEl.value = '0.00';
                statusEl.textContent = 'Choose two different landmarks to draw a road route.';
                return;
            }

            const url = 'https://router.project-osrm.org/route/v1/driving/' +
                pointA.dataset.lng + ',' + pointA.dataset.lat + ';' +
                pointB.dataset.lng + ',' + pointB.dataset.lat +
                '?overview=full&geometries=geojson';

            statusEl.textContent = 'Loading road route...';
            try {
                const response = await fetch(url);
                if (!response.ok) throw new Error('Routing service unavailable.');
                const data = await response.json();
                if (data.code !== 'Ok' || !data.routes || !data.routes.length) {
                    throw new Error('No road route was found for these landmarks.');
                }

                const route = data.routes[0];
                distanceEl.value = (route.distance / 1000).toFixed(2);
                routeLine = L.geoJSON(route.geometry, {
                    style: { color: '#0f766e', weight: 4 },
                }).addTo(map);
                map.fitBounds(routeLine.getBounds(), { padding: [24, 24] });
                statusEl.textContent = 'Road distance updated from OpenStreetMap routing.';
            } catch (error) {
                distanceEl.value = '';
                statusEl.textContent = error.message;
            }
        }

        pointAEl.addEventListener('change', updateRoute);
        pointBEl.addEventListener('change', updateRoute);
        searchButton.addEventListener('click', searchPlaces);
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchPlaces();
            }
        });
        updateRoute();
    }

    document.addEventListener('DOMContentLoaded', initMap);
})();
