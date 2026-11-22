from django.core.management.base import BaseCommand
from django.db import transaction

from places.models import LAT_MAX, LAT_MIN, LON_MAX, LON_MIN, Place, SubCategory


MUNICIPALITIES = (
    ('بلدية الشاغور', 33.5076131, 36.3012264),
    ('بلدية كفرسوسة', 33.5007323, 36.2773436),
    ('بلدية المزة الغربي', 33.5049543, 36.2519143),
    ('بلدية القدم', 33.4721723, 36.2873402),
    ('بلدية عين ترما', 33.5170036, 36.3510371),
    ('بلدية دمر', 33.5407364, 36.2391107),
    ('بلدية دوما', 33.5716645, 36.3948033),
)


class Command(BaseCommand):
    help = 'Seed seven municipalities from their Google Maps place coordinates.'

    @transaction.atomic
    def handle(self, *args, **options):
        subcategory = SubCategory.objects.get(
            category__slug='damascus-governorate', slug='municipalities'
        )
        created = 0
        for name, lat, lon in MUNICIPALITIES:
            if not (LAT_MIN <= lat <= LAT_MAX and LON_MIN <= lon <= LON_MAX):
                raise ValueError(f'Coordinates outside bounds: {name}')
            _, is_new = Place.objects.update_or_create(
                title_ar=name,
                sub_category=subcategory,
                defaults={'latitude': lat, 'longitude': lon, 'sector': ''},
            )
            created += is_new
        self.stdout.write(self.style.SUCCESS(
            f'Processed {len(MUNICIPALITIES)} municipalities ({created} new).'
        ))
