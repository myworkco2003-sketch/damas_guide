import json
from pathlib import Path

from django.core.management.base import BaseCommand
from django.db import transaction

from places.models import LAT_MAX, LAT_MIN, LON_MAX, LON_MIN, Place, SubCategory


class Command(BaseCommand):
    help = 'Seed police stations from places/data/police_stations_new1.json.'

    @transaction.atomic
    def handle(self, *args, **options):
        path = Path(__file__).resolve().parents[2] / 'data' / 'police_stations_new1.json'
        entries = json.loads(path.read_text(encoding='utf-8'))
        subcategory = SubCategory.objects.get(
            category__slug='government', slug='police-stations'
        )
        created = 0
        for entry in entries:
            name, lat, lon = entry['name'], entry['latitude'], entry['longitude']
            if not (LAT_MIN <= lat <= LAT_MAX and LON_MIN <= lon <= LON_MAX):
                raise ValueError(f'Coordinates outside bounds: {name}')
            _, is_new = Place.objects.update_or_create(
                title_ar=name,
                sub_category=subcategory,
                defaults={'latitude': lat, 'longitude': lon, 'sector': ''},
            )
            created += is_new
        self.stdout.write(self.style.SUCCESS(
            f'Processed {len(entries)} police stations ({created} new).'
        ))
