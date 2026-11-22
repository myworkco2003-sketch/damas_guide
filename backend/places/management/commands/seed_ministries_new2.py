from django.core.management.base import BaseCommand
from django.db import transaction

from places.models import LAT_MAX, LAT_MIN, LON_MAX, LON_MIN, Place, SubCategory


MINISTRIES = (
    ('وزارة الطوارئ وإدارة الكوارث', 33.5067786, 36.2872343),
    ('وزارة الصناعة', 33.5164546, 36.2925967),
    ('وزارة الصحة', 33.5174396, 36.291158),
    ('وزارة الداخلية السورية', 33.5045827, 36.2785058),
    ('وزارة المالية', 33.5217595, 36.2972295),
    ('وزارة الكهرباء', 33.5213076, 36.289209),
    ('وزارة الثقافة', 33.5229869, 36.2805817),
    ('وزارة التربية', 33.5254314, 36.2919751),
    ('وزارة الإدارة المحلية', 33.5049972, 36.2808781),
)


class Command(BaseCommand):
    help = 'Seed nine ministries from their Google Maps place coordinates.'

    @transaction.atomic
    def handle(self, *args, **options):
        subcategory = SubCategory.objects.get(
            category__slug='government', slug='ministries'
        )
        created = 0
        for name, lat, lon in MINISTRIES:
            if not (LAT_MIN <= lat <= LAT_MAX and LON_MIN <= lon <= LON_MAX):
                raise ValueError(f'Coordinates outside bounds: {name}')
            _, is_new = Place.objects.update_or_create(
                title_ar=name,
                sub_category=subcategory,
                defaults={'latitude': lat, 'longitude': lon, 'sector': ''},
            )
            created += is_new
        self.stdout.write(self.style.SUCCESS(
            f'Processed {len(MINISTRIES)} ministries ({created} new).'
        ))
