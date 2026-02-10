#!/usr/bin/env python3
"""
Configure les règles CORS sur le bucket Cloudflare R2 (ciaocv)
pour restreindre l'accès aux domaines *.ciaocv.com uniquement.

Utilise l'API S3 PutBucketCors via signature AWS4.

Usage:
    python3 scripts/r2-set-cors.py          # applique les CORS
    python3 scripts/r2-set-cors.py --check  # vérifie les CORS actuels
"""

import hashlib
import hmac
import os
import sys
import urllib.request
import urllib.error
from datetime import datetime, timezone

# ─── Charger .env ───────────────────────────────────────────────────────────
def load_env(path):
    """Parse un fichier .env basique."""
    env = {}
    if not os.path.exists(path):
        return env
    with open(path) as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith('#'):
                continue
            if '=' not in line:
                continue
            key, val = line.split('=', 1)
            val = val.strip().strip('"').strip("'")
            env[key] = val
    return env

env_path = os.path.join(os.path.dirname(__file__), '..', '.env')
env = load_env(env_path)

ACCESS_KEY = env.get('R2_ACCESS_KEY_ID', '')
SECRET_KEY = env.get('R2_SECRET_ACCESS_KEY', '')
ENDPOINT   = env.get('R2_ENDPOINT', '').rstrip('/')
BUCKET     = env.get('R2_BUCKET', 'ciaocv')

if not ACCESS_KEY or not SECRET_KEY or not ENDPOINT:
    print("❌ Variables R2 manquantes dans .env")
    sys.exit(1)

HOST = ENDPOINT.replace('https://', '').replace('http://', '')

# ─── CORS XML ───────────────────────────────────────────────────────────────
CORS_XML = """<?xml version="1.0" encoding="UTF-8"?>
<CORSConfiguration>
  <CORSRule>
    <AllowedOrigin>https://app.ciaocv.com</AllowedOrigin>
    <AllowedOrigin>https://www.ciaocv.com</AllowedOrigin>
    <AllowedOrigin>https://ciaocv.com</AllowedOrigin>
    <AllowedOrigin>https://gestion.ciaocv.com</AllowedOrigin>
    <AllowedMethod>GET</AllowedMethod>
    <AllowedMethod>PUT</AllowedMethod>
    <AllowedMethod>HEAD</AllowedMethod>
    <AllowedHeader>*</AllowedHeader>
    <ExposeHeader>ETag</ExposeHeader>
    <MaxAgeSeconds>3600</MaxAgeSeconds>
  </CORSRule>
</CORSConfiguration>""".strip()

# ─── AWS Signature V4 ──────────────────────────────────────────────────────
def sign(key, msg):
    return hmac.new(key, msg.encode('utf-8'), hashlib.sha256).digest()

def get_signature_key(secret, datestamp, region, service):
    k_date    = sign(('AWS4' + secret).encode('utf-8'), datestamp)
    k_region  = sign(k_date, region)
    k_service = sign(k_region, service)
    k_signing = sign(k_service, 'aws4_request')
    return k_signing

def s3_request(method, path, body=b'', query=''):
    now = datetime.now(timezone.utc)
    datestamp = now.strftime('%Y%m%d')
    amz_date = now.strftime('%Y%m%dT%H%M%SZ')
    region = 'auto'
    service = 's3'

    payload_hash = hashlib.sha256(body).hexdigest()
    canonical_uri = '/' + BUCKET + path

    headers_to_sign = {
        'host': HOST,
        'x-amz-content-sha256': payload_hash,
        'x-amz-date': amz_date,
    }
    if method == 'PUT' and body:
        headers_to_sign['content-type'] = 'application/xml'
        # Add content-md5 for PutBucketCors
        import base64
        md5 = base64.b64encode(hashlib.md5(body).digest()).decode()
        headers_to_sign['content-md5'] = md5

    signed_header_keys = sorted(headers_to_sign.keys())
    signed_headers_str = ';'.join(signed_header_keys)
    canonical_headers = ''.join(f'{k}:{headers_to_sign[k]}\n' for k in signed_header_keys)

    canonical_request = '\n'.join([
        method,
        canonical_uri,
        query,
        canonical_headers,
        signed_headers_str,
        payload_hash,
    ])

    scope = f'{datestamp}/{region}/{service}/aws4_request'
    string_to_sign = '\n'.join([
        'AWS4-HMAC-SHA256',
        amz_date,
        scope,
        hashlib.sha256(canonical_request.encode()).hexdigest(),
    ])

    signing_key = get_signature_key(SECRET_KEY, datestamp, region, service)
    signature = hmac.new(signing_key, string_to_sign.encode(), hashlib.sha256).hexdigest()

    auth = (
        f'AWS4-HMAC-SHA256 Credential={ACCESS_KEY}/{scope}, '
        f'SignedHeaders={signed_headers_str}, '
        f'Signature={signature}'
    )

    url = f'{ENDPOINT}{canonical_uri}'
    if query:
        url += '?' + query

    req = urllib.request.Request(url, data=body if body else None, method=method)
    req.add_header('Authorization', auth)
    for k, v in headers_to_sign.items():
        req.add_header(k, v)

    try:
        with urllib.request.urlopen(req) as resp:
            return resp.status, resp.read().decode()
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode()

# ─── Main ───────────────────────────────────────────────────────────────────
if __name__ == '__main__':
    if '--check' in sys.argv:
        print("🔍 Vérification des CORS actuels…")
        status, body = s3_request('GET', '/', query='cors')
        print(f"   HTTP {status}")
        print(body)
    else:
        print("⚙️  Application des règles CORS sur le bucket R2…")
        print(f"   Bucket : {BUCKET}")
        print(f"   Origines autorisées :")
        print("     - https://app.ciaocv.com")
        print("     - https://www.ciaocv.com")
        print("     - https://ciaocv.com")
        print("     - https://gestion.ciaocv.com")
        print()

        body = CORS_XML.encode('utf-8')
        status, resp = s3_request('PUT', '/', body=body, query='cors')

        if status in (200, 204):
            print(f"✅ CORS configurés avec succès (HTTP {status})")
        else:
            print(f"❌ Erreur HTTP {status}")
            print(resp)
            sys.exit(1)

        # Vérifier
        print("\n🔍 Vérification…")
        status, body = s3_request('GET', '/', query='cors')
        print(f"   HTTP {status}")
        print(body)
