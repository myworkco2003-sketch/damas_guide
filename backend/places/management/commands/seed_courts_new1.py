from django.core.management.base import BaseCommand
from django.db import transaction

from places.models import LAT_MAX, LAT_MIN, LON_MAX, LON_MIN, Place, SubCategory


COURTS = (
    ('قصر العدل', 33.5105739, 36.2997746),
    ('محكمة ريف دمشق البدايات المدنية', 33.5127867, 36.2980744),
    ('القضاء العسكري', 33.5100441, 36.2725895),
    ('محكمة صلح جزاء المزة الخامسة', 33.50502, 36.2516823),
    ('المحكمة العدلية القصر العدلي', 33.4983361, 36.2473478),
)


class Command(BaseCommand):
    help = 'Seed five courts from their Google Maps place coordinates.'

    @transaction.atomic
    def handle(self, *args, **options):
        subcategory = SubCategory.objects.get(
            category__slug='government', slug='courts'
        )
        created = 0
        for name, lat, lon in COURTS:
            if not (LAT_MIN <= lat <= LAT_MAX and LON_MIN <= lon <= LON_MAX):
                raise ValueError(f'Coordinates outside bounds: {name}')
            _, is_new = Place.objects.update_or_create(
                title_ar=name,
                sub_category=subcategory,
                defaults={'latitude': lat, 'longitude': lon, 'sector': ''},
            )
            created += is_new
        self.stdout.write(self.style.SUCCESS(
            f'Processed {len(COURTS)} courts ({created} new).'
        ))
