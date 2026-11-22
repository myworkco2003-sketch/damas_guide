import json
from pathlib import Path

from django.core.management.base import BaseCommand
from django.db import transaction

from places.models import LAT_MAX, LAT_MIN, LON_MAX, LON_MIN, Place, SubCategory


class Command(BaseCommand):
    help = 'Seed service centers from places/data/communications_service_centers_new1.json.'

    @transaction.atomic
    def handle(self, *args, **options):
        path = Path(__file__).resolve().parents[2] / 'data' / 'communications_service_centers_new1.json'
        entries = json.loads(path.read_text(encoding='utf-8'))
        subcategory = SubCategory.objects.get(
            category__slug='communications', slug='mobile-service-centers'
        )
        created = 0
        for entry in entries:
            name, lat, lon = entry['name'], entry['latitude'], entry['longitude']
            if not (LAT_MIN <= lat <= LAT_MAX and LON_MIN <= lon <= LON_MAX):
                raise ValueError(f'Coordinates outside bounds: {name}')
            place = Place.objects.filter(sub_category=subcategory, title_ar=name).first()
            if not place and entry.get('legacy_name'):
                place = Place.objects.filter(
                    sub_category=subcategory, title_ar=entry['legacy_name']
                ).first()
            if place:
                place.title_ar, place.latitude, place.longitude, place.sector = name, lat, lon, ''
                place.save()
            else:
                Place.objects.create(
                    title_ar=name, sub_category=subcategory,
                    latitude=lat, longitude=lon, sector='',
                )
                created += 1
        self.stdout.write(self.style.SUCCESS(
            f'Processed {len(entries)} service centers ({created} new).'
        ))
