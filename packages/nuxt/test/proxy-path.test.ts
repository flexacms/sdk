import { describe, expect, it } from 'vitest';
import { isEntryPath, proxyRequestPath } from '../src/runtime/proxy-path';

describe('proxyRequestPath', () => {
    it('passes reads of entities, entries and schemas', () => {
        expect(proxyRequestPath('entities', {})).toBe('/entities');
        expect(
            proxyRequestPath('posts', {
                status: 'published',
                page: 2,
                per_page: 10,
                include: 'relations',
            }),
        ).toBe('/posts?status=published&page=2&per_page=10&include=relations');
        expect(proxyRequestPath('posts/hello world', {})).toBe(
            '/posts/hello%20world',
        );
        expect(proxyRequestPath('posts/schema', {})).toBe('/posts/schema');
    });

    it('drops parameters the browser must not control', () => {
        expect(
            proxyRequestPath('posts', {
                trashed: 'with',
                force: '1',
                include: 'secrets',
                search: ['a', 'b'],
            }),
        ).toBe('/posts?status=published');
    });

    it('only lists published entries', () => {
        expect(proxyRequestPath('posts', { status: 'draft' })).toBe(
            '/posts?status=published',
        );
        expect(proxyRequestPath('entities', { status: 'draft' })).toBe(
            '/entities',
        );
    });

    it('marks single entry reads, whose status the proxy checks', () => {
        expect(isEntryPath('/posts/hello')).toBe(true);
        expect(isEntryPath('/posts/schema')).toBe(false);
        expect(isEntryPath('/posts?status=published')).toBe(false);
        expect(isEntryPath('/entities')).toBe(false);
    });

    it('refuses paths outside the read endpoints', () => {
        expect(proxyRequestPath('', {})).toBeNull();
        expect(proxyRequestPath('posts/1/restore', {})).toBeNull();
        expect(proxyRequestPath('entities/extra', {})).toBeNull();
        expect(proxyRequestPath('../admin', {})).toBeNull();
    });
});
