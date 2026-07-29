import { useForm } from '@inertiajs/react';
import { CooperativeForm } from './Create';

export default function CooperativesEdit({ cooperative, provinces }: any) {
    const form = useForm({ nik: cooperative.nik, name: cooperative.name, province_id: cooperative.province_id, city_id: cooperative.city_id, district_id: cooperative.district_id, village_id: cooperative.village_id, latitude: cooperative.latitude ?? '', longitude: cooperative.longitude ?? '', is_active: !!cooperative.is_active });
    return <CooperativeForm title="Edit Cooperative" form={form} provinces={provinces} onSubmit={() => form.put(`/cooperatives/${cooperative.id}`)} />;
}
