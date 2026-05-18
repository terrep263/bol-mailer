#!/usr/bin/env python3
"""
S3 utility script for bol-mailer.
Usage:
  python3 scripts/s3.py upload <local_path> <s3_key> [--content-type <type>]
  python3 scripts/s3.py download <s3_key> <local_path>
  python3 scripts/s3.py delete <s3_key>
  python3 scripts/s3.py list [prefix]
  python3 scripts/s3.py url <s3_key>
  python3 scripts/s3.py presign <s3_key> [--expires <seconds>]

Credentials are read from environment variables:
  AWS_ACCESS_KEY_ID
  AWS_SECRET_ACCESS_KEY
  AWS_S3_BUCKET
  AWS_S3_REGION (default: us-east-1)
"""

import argparse
import os
import sys
import boto3
from botocore.exceptions import ClientError

# Credentials from env
AWS_ACCESS_KEY_ID = os.environ.get("AWS_ACCESS_KEY_ID")
AWS_SECRET_ACCESS_KEY = os.environ.get("AWS_SECRET_ACCESS_KEY")
AWS_S3_BUCKET = os.environ.get("AWS_S3_BUCKET", "bookoflies-853537565894-us-east-1-an")
AWS_S3_REGION = os.environ.get("AWS_S3_REGION", "us-east-1")

if not AWS_ACCESS_KEY_ID or not AWS_SECRET_ACCESS_KEY:
    print("ERROR: AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY must be set")
    sys.exit(1)

s3 = boto3.client(
    's3',
    region_name=AWS_S3_REGION,
    aws_access_key_id=AWS_ACCESS_KEY_ID,
    aws_secret_access_key=AWS_SECRET_ACCESS_KEY
)

BASE_URL = f"https://{AWS_S3_BUCKET}.s3.{AWS_S3_REGION}.amazonaws.com"


def upload(local_path, s3_key, content_type=None):
    if not os.path.exists(local_path):
        print(f"ERROR: File not found: {local_path}")
        sys.exit(1)
    extra = {}
    if content_type:
        extra['ContentType'] = content_type
    else:
        # Auto-detect common types
        ext = os.path.splitext(local_path)[1].lower()
        types = {
            '.pdf': 'application/pdf',
            '.epub': 'application/epub+zip',
            '.png': 'image/png',
            '.jpg': 'image/jpeg',
            '.jpeg': 'image/jpeg',
            '.mp3': 'audio/mpeg',
            '.mp4': 'video/mp4',
            '.zip': 'application/zip',
            '.txt': 'text/plain',
            '.html': 'text/html',
            '.json': 'application/json',
        }
        if ext in types:
            extra['ContentType'] = types[ext]
    try:
        s3.upload_file(local_path, AWS_S3_BUCKET, s3_key, ExtraArgs=extra if extra else None)
        print(f"UPLOADED: {BASE_URL}/{s3_key}")
    except ClientError as e:
        print(f"ERROR: {e}")
        sys.exit(1)


def download(s3_key, local_path):
    try:
        s3.download_file(AWS_S3_BUCKET, s3_key, local_path)
        print(f"DOWNLOADED: {s3_key} → {local_path}")
    except ClientError as e:
        print(f"ERROR: {e}")
        sys.exit(1)


def delete(s3_key):
    try:
        s3.delete_object(Bucket=AWS_S3_BUCKET, Key=s3_key)
        print(f"DELETED: {s3_key}")
    except ClientError as e:
        print(f"ERROR: {e}")
        sys.exit(1)


def list_files(prefix=None):
    try:
        kwargs = {'Bucket': AWS_S3_BUCKET}
        if prefix:
            kwargs['Prefix'] = prefix
        response = s3.list_objects_v2(**kwargs)
        files = response.get('Contents', [])
        if not files:
            print("Bucket is empty" if not prefix else f"No files with prefix: {prefix}")
            return
        print(f"{'Key':<60} {'Size':>10} {'Modified'}")
        print("-" * 90)
        for obj in sorted(files, key=lambda x: x['LastModified'], reverse=True):
            size = f"{obj['Size']:,}"
            modified = obj['LastModified'].strftime('%Y-%m-%d %H:%M')
            print(f"{obj['Key']:<60} {size:>10} {modified}")
        print(f"\nTotal: {len(files)} file(s)")
    except ClientError as e:
        print(f"ERROR: {e}")
        sys.exit(1)


def get_url(s3_key):
    print(f"{BASE_URL}/{s3_key}")


def presign(s3_key, expires=3600):
    try:
        url = s3.generate_presigned_url(
            'get_object',
            Params={'Bucket': AWS_S3_BUCKET, 'Key': s3_key},
            ExpiresIn=expires
        )
        print(f"PRESIGNED URL (expires in {expires}s):\n{url}")
    except ClientError as e:
        print(f"ERROR: {e}")
        sys.exit(1)


def main():
    parser = argparse.ArgumentParser(description='S3 utility for bol-mailer')
    subparsers = parser.add_subparsers(dest='command')

    # upload
    up = subparsers.add_parser('upload')
    up.add_argument('local_path')
    up.add_argument('s3_key')
    up.add_argument('--content-type')

    # download
    dl = subparsers.add_parser('download')
    dl.add_argument('s3_key')
    dl.add_argument('local_path')

    # delete
    rm = subparsers.add_parser('delete')
    rm.add_argument('s3_key')

    # list
    ls = subparsers.add_parser('list')
    ls.add_argument('prefix', nargs='?', default=None)

    # url
    url = subparsers.add_parser('url')
    url.add_argument('s3_key')

    # presign
    ps = subparsers.add_parser('presign')
    ps.add_argument('s3_key')
    ps.add_argument('--expires', type=int, default=3600)

    args = parser.parse_args()

    if args.command == 'upload':
        upload(args.local_path, args.s3_key, getattr(args, 'content_type', None))
    elif args.command == 'download':
        download(args.s3_key, args.local_path)
    elif args.command == 'delete':
        delete(args.s3_key)
    elif args.command == 'list':
        list_files(args.prefix)
    elif args.command == 'url':
        get_url(args.s3_key)
    elif args.command == 'presign':
        presign(args.s3_key, args.expires)
    else:
        parser.print_help()


if __name__ == '__main__':
    main()
