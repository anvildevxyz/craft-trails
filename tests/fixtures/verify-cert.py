#!/usr/bin/env python3
"""
Independent HMAC-SHA256 verifier for Trails compliance certificates.

Reads the security key from $CRAFT_SECURITY_KEY (or the path passed as --key-file).
Recomputes the signature over the JSON payload (with the `signature` field removed)
encoded with separators=(',', ':'), ensure_ascii=False — matching the PHP encoding:
json_encode($cert, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).

Exit codes:
    0  signature matches
    1  signature mismatch
    2  malformed certificate (cannot parse / missing signature)
    3  missing security key

Usage:
    CRAFT_SECURITY_KEY=... ./verify-cert.py path/to/cert.json
    ./verify-cert.py --key-file /path/to/key.txt path/to/cert.json
"""
from __future__ import annotations

import argparse
import hashlib
import hmac
import json
import os
import sys


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("cert", help="Path to a trails-certificate-*.json file")
    parser.add_argument("--key-file", help="Read the security key from this file (overrides $CRAFT_SECURITY_KEY)")
    parser.add_argument("--quiet", "-q", action="store_true", help="No output on success; only print on failure")
    args = parser.parse_args()

    if args.key_file:
        try:
            with open(args.key_file, "rb") as f:
                key = f.read().strip()
        except OSError as exc:
            print(f"verify-cert: cannot read --key-file: {exc}", file=sys.stderr)
            return 3
    else:
        key_str = os.environ.get("CRAFT_SECURITY_KEY", "")
        if not key_str:
            print(
                "verify-cert: $CRAFT_SECURITY_KEY is not set and --key-file was not given.",
                file=sys.stderr,
            )
            return 3
        key = key_str.encode("utf-8")

    try:
        with open(args.cert, "rb") as f:
            cert = json.load(f)
    except (OSError, json.JSONDecodeError) as exc:
        print(f"verify-cert: cannot parse certificate: {exc}", file=sys.stderr)
        return 2

    if "signature" not in cert:
        print("verify-cert: certificate is missing a `signature` field", file=sys.stderr)
        return 2

    stored = cert.pop("signature")
    payload = json.dumps(cert, separators=(",", ":"), ensure_ascii=False)
    recomputed = hmac.new(key, payload.encode("utf-8"), hashlib.sha256).hexdigest()

    if hmac.compare_digest(stored, recomputed):
        if not args.quiet:
            print(f"OK: signature matches ({stored[:16]}...)")
        return 0

    print(f"FAIL: signature mismatch", file=sys.stderr)
    print(f"  stored:     {stored}", file=sys.stderr)
    print(f"  recomputed: {recomputed}", file=sys.stderr)
    return 1


if __name__ == "__main__":
    sys.exit(main())
