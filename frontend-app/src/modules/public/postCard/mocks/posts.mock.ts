import type { Post } from '../types/post.types'

const users = [
  { id: 'u1', name: 'Ava Martin', username: 'ava', avatarUrl: 'https://i.pravatar.cc/80?img=1', verified: true },
  { id: 'u2', name: 'Leo Schmidt', username: 'leo', avatarUrl: 'https://i.pravatar.cc/80?img=2' },
  { id: 'u3', name: 'Mia Rossi', username: 'mia', avatarUrl: 'https://i.pravatar.cc/80?img=3' },
  { id: 'u4', name: 'Noah Kim', username: 'noah', avatarUrl: 'https://i.pravatar.cc/80?img=4' },
]

function rnd(min: number, max: number) {
  return Math.floor(Math.random() * (max - min + 1)) + min
}

function timeAgoMinutes(minutes: number) {
  const d = new Date(Date.now() - minutes * 60000)
  return d.toISOString()
}

const texts = [
  'Lorem Ipsum is #test simply dummy @rezyyy text of the printing http://app.localhost:8080/ and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
  '<h2><strong>Lorem Ipsum</strong></h2><h3><strong>is simply dummy text of the printing and typesetting</strong></h3><p> is simply dummy #test text of the @Rezyy printing and  http://app.localhost:8080/auth/logintypesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum. is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.<strong>Lorem Ipsum</strong> is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>',
  'Hello @rezyyy check http://app.localhost:8080/ #test\n',
  'Prototype v2 feels much smoother.',
  'Reading a great article on design systems.',
  'Sketching ideas for the new onboarding.',
  'Small wins add up.',
  'Coffee then code.',
  'Deploying fix for the image loader.',
  'Weekend hike plans?',
]

export const mockPosts: Post[] = Array.from({ length: 10 }).map((_, i) => {
  const u = users[i % users.length]
  const kind = i % 6
  const base: Omit<Post, 'media'> = {
    id: `p${i + 1}`,
    author: u,
    createdAt: timeAgoMinutes(rnd(10, 5000)),
    editedAt: null,
    visibility: 'public',
    text: texts[i],
    counts: {
      likes: rnd(0, 1200),
      comments: rnd(0, 300),
      shares: rnd(0, 150),
      saves: rnd(0, 200),
      views: rnd(50, 5000),
    },
    isPinned: i === 0,
    isRepost: i === 7,
    originalPostId: i === 7 ? 'p2' : null,
  }

  if (kind === 0) {
    return {
      ...base,
      media: [
        {
          id: `m${i}-img`,
          type: 'image',
          url: `https://picsum.photos/id/${100 + i}/1200/800`,
          alt: 'random',
          aspectRatio: '3/2',
          width: 1200,
          height: 800,
        },
      ],
    }
  }

  if (kind === 1) {
    return {
      ...base,
      media: [
        {
          id: `m${i}-vid`,
          type: 'video',
          url: 'https://storage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
          poster: `https://picsum.photos/id/${120 + i}/1200/675`,
        },
      ],
    }
  }

  if (kind === 2) {
    return {
      ...base,
      media: [
        {
          id: `m${i}-gal`,
          type: 'gallery',
          items: [
            { id: `g${i}-1`, url: `https://picsum.photos/id/${140 + i}/800/800`, alt: 'g1', aspectRatio: '1/1' },
            { id: `g${i}-2`, url: `https://picsum.photos/id/${141 + i}/800/1000`, alt: 'g2', aspectRatio: '4/5' },
            { id: `g${i}-3`, url: `https://picsum.photos/id/${142 + i}/1200/800`, alt: 'g3', aspectRatio: '3/2' },
          ],
        },
      ],
    }
  }

  if (kind === 3) {
    return {
      ...base,
      media: [
        {
          id: `m${i}-link`,
          type: 'link',
          url: 'https://example.com/articles/design-systems',
          title: 'Design Systems That Scale',
          description: 'Principles and patterns for sustainable product design.',
          image: `https://picsum.photos/id/${160 + i}/1200/630`,
          domain: 'example.com',
        },
      ],
    }
  }

  if (kind === 4) {
    return {
      ...base,
      media: [
        {
          id: `m${i}-doc`,
          type: 'document',
          url: 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
          filename: 'Design-Guidelines.pdf',
          mime: 'application/pdf',
          sizeBytes: 245760,
          thumbnailUrl: `https://picsum.photos/id/${170 + i}/300/400`,
        },
      ],
    }
  }

  return {
    ...base,
    media: [
      {
        id: `m${i}-aud`,
        type: 'audio',
        url: 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
        title: 'Focus Session',
        mime: 'audio/mpeg',
        durationSec: rnd(120, 480),
        coverUrl: `https://picsum.photos/id/${180 + i}/300/300`,
      },
    ],
  }
})

export default mockPosts
