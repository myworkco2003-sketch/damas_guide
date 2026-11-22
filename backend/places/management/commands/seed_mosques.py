import json
from pathlib import Path

from django.core.management.base import BaseCommand
from django.db import transaction

from places.models import Place, SubCategory, LAT_MIN, LAT_MAX, LON_MIN, LON_MAX


class Command(BaseCommand):
    help = 'Seed Damascus mosques from places/data/mosques.json.'

    @transaction.atomic
    def handle(self, *args, **options):
        path = Path(__file__).resolve().parents[2] / 'data' / 'mosques.json'
        entries = json.loads(path.read_text(encoding='utf-8'))
        subcategory = SubCategory.objects.get(category__slug='religious', slug='mosques')
        created = 0

        for entry in entries:
            lat, lon = entry['coordinates']
            if not (LAT_MIN <= lat <= LAT_MAX and LON_MIN <= lon <= LON_MAX):
                raise ValueError(f"Coordinates outside Damascus bounds: {entry['name']}")
            _, is_new = Place.objects.update_or_create(
                title_ar=entry['name'],
                defaults={
                    'title_en': entry.get('name_en', ''),
                    'sub_category': subcategory,
                    'latitude': lat,
                    'longitude': lon,
                    'description': '',
                    'sector': 'Public',
                    'is_visible': True,
                    'is_verified': False,
                },
            )
            created += is_new

        self.stdout.write(self.style.SUCCESS(
            f'Processed {len(entries)} mosques ({created} new).'
        ))
