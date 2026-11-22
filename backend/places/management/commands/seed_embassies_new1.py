from django.core.management.base import BaseCommand
from django.db import transaction

from places.models import LAT_MAX, LAT_MIN, LON_MAX, LON_MIN, Place, SubCategory


EMBASSIES = (
    ('السفارة التركية', 33.5213456, 36.2836569),
    ('سفارة تشيلي', 33.5240249, 36.2826995),
    ('سفارة اليابان', 33.5161334, 36.2876131),
    ('السفارة المصرية', 33.5042369, 36.2794197),
)


class Command(BaseCommand):
    help = 'Seed four embassies from their Google Maps place coordinates.'

    @transaction.atomic
    def handle(self, *args, **options):
        subcategory = SubCategory.objects.get(
            category__slug='government', slug='embassies-consulates'
        )
        created = 0
        for name, lat, lon in EMBASSIES:
            if not (LAT_MIN <= lat <= LAT_MAX and LON_MIN <= lon <= LON_MAX):
                raise ValueError(f'Coordinates outside bounds: {name}')
            _, is_new = Place.objects.update_or_create(
                title_ar=name,
                sub_category=subcategory,
                defaults={'latitude': lat, 'longitude': lon, 'sector': ''},
            )
            created += is_new
        self.stdout.write(self.style.SUCCESS(
            f'Processed {len(EMBASSIES)} embassies ({created} new).'
        ))
