import json
from pathlib import Path
from django.core.management.base import BaseCommand
from django.db import transaction
from places.models import AdminArea


class Command(BaseCommand):
    help = "Import Damascus sectors, municipalities and neighborhoods from GeoJSON"

    @transaction.atomic
    def handle(self, *args, **options):
        geojson_path = Path(__file__).resolve().parent.parent.parent.parent / "data" / "damascus_admin_areas.geojson"
        
        if not geojson_path.exists():
            self.stderr.write(self.style.ERROR(f"GeoJSON file not found: {geojson_path}"))
            return

        with open(geojson_path, "r", encoding="utf-8") as f:
            data = json.load(f)

        features = data.get("features", [])
        self.stdout.write(f"Found {len(features)} features in GeoJSON. Beginning import...")

        for feature in sorted(features, key=lambda item: item["properties"]["level"]):
            props = feature["properties"]
            AdminArea.objects.update_or_create(
                id=props["id"],
                defaults={
                    "level": props["level"], "parent_id": props["parent_id"],
                    "name_ar": props["name_ar"], "name_en": props.get("name_en") or "",
                    "code": props.get("code") or "", "pcode": props.get("pcode"),
                    "boundary": feature["geometry"],
                },
            )
        self.stdout.write(self.style.SUCCESS(f"Imported {len(features)} administrative areas"))
