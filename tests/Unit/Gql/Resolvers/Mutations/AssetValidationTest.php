<?php

declare(strict_types=1);

use CraftCms\Cms\Gql\Resolvers\Mutations\Asset;

beforeEach(function () {
    $this->resolver = new Asset;
});

it('rejects IP addresses as hostnames', function (string $url) {
    $method = new ReflectionMethod($this->resolver, 'validateHostname');

    expect($method->invoke($this->resolver, $url))->toBeFalse();
})->with([
    'IPv4 address' => ['http://192.168.1.1/file.jpg'],
    'loopback' => ['http://127.0.0.1/file.jpg'],
    'cloud metadata IP' => ['http://169.254.169.254/latest/meta-data/'],
]);

it('rejects cloud metadata hostnames', function (string $url) {
    $method = new ReflectionMethod($this->resolver, 'validateHostname');

    expect($method->invoke($this->resolver, $url))->toBeFalse();
})->with([
    'kubernetes.default' => ['http://kubernetes.default/api'],
    'kubernetes.default.svc' => ['http://kubernetes.default.svc/api'],
    'kubernetes.default.svc.cluster.local' => ['http://kubernetes.default.svc.cluster.local/api'],
    'metadata' => ['http://metadata/computeMetadata/v1/'],
    'metadata.google.internal' => ['http://metadata.google.internal/computeMetadata/v1/'],
    'metadata.packet.net' => ['http://metadata.packet.net/metadata'],
]);

it('rejects hex-encoded hostnames', function () {
    $method = new ReflectionMethod($this->resolver, 'validateHostname');

    // 0xa9.0xfe.0xa9.0xfe = 169.254.169.254
    expect($method->invoke($this->resolver, 'http://0xa9.0xfe.0xa9.0xfe/latest/'))->toBeFalse();
});

it('accepts valid public hostnames', function (string $url) {
    $method = new ReflectionMethod($this->resolver, 'validateHostname');

    expect($method->invoke($this->resolver, $url))->toBeTrue();
})->with([
    'example.com' => ['https://example.com/image.jpg'],
    'cdn.example.com' => ['https://cdn.example.com/assets/photo.png'],
    'images.unsplash.com' => ['https://images.unsplash.com/photo-123.jpg'],
]);

it('rejects known cloud metadata IPs', function (string $ip) {
    $method = new ReflectionMethod($this->resolver, 'validateIp');

    expect($method->invoke($this->resolver, $ip))->toBeFalse();
})->with([
    'AWS/GCP/Azure metadata' => ['169.254.169.254'],
    'ECS metadata' => ['169.254.170.2'],
    'Alibaba metadata' => ['100.100.100.200'],
    'Oracle metadata' => ['192.0.0.192'],
]);

it('rejects private and reserved IP ranges', function (string $ip) {
    $method = new ReflectionMethod($this->resolver, 'validateIp');

    expect($method->invoke($this->resolver, $ip))->toBeFalse();
})->with([
    '10.x range' => ['10.0.0.1'],
    '172.16.x range' => ['172.16.0.1'],
    '192.168.x range' => ['192.168.1.1'],
    'loopback' => ['127.0.0.1'],
]);

it('rejects dangerous IPv6 addresses', function (string $ip) {
    $method = new ReflectionMethod($this->resolver, 'validateIp');

    expect($method->invoke($this->resolver, $ip))->toBeFalse();
})->with([
    'loopback v6' => ['::1'],
    'IPv4-mapped v6' => ['::ffff:169.254.169.254'],
    'link-local v6' => ['fe80:1234::1'],
    'AWS IMDS v6' => ['fd00:ec2::1'],
    'GCP v6' => ['fd20:ce::1'],
]);

it('accepts valid public IPs', function (string $ip) {
    $method = new ReflectionMethod($this->resolver, 'validateIp');

    expect($method->invoke($this->resolver, $ip))->toBeTrue();
})->with([
    'Google DNS' => ['8.8.8.8'],
    'Cloudflare DNS' => ['1.1.1.1'],
    'Random public IP' => ['203.0.113.50'],
]);
