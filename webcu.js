import http from 'k6/http';
import { sleep, check } from 'k6';

export const options = {
  vus: 1500,          // Jumlah Virtual Users
  duration: '1m',   // Lama pengujian
};

export default function () {
  const res = http.get('https://pkkmb.bemftunesa.org/');

  check(res, {
    'Status 200': (r) => r.status === 200,
  });

  sleep(1);
}