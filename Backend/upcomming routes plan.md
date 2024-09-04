## Subreddit Entity

**id** (integer, read-only)  
- The unique identifier of the subreddit.

**name** (string, required)  
- The name of the subreddit. Must be between 3 and 21 characters long, also identifier visible to users.

**description** (string, optional)  
- A brief description of the subreddit. Must be between 0 and 500 characters long.

**status** (Enum, required)  
- The status of the subreddit. Values: `public`, `private`, `restricted`.

**isNsfw** (boolean, optional)  
- Whether the subreddit is marked as NSFW (Not Safe for Work).

**AmountOfMembers** (integer, read-only)  
- The number of members in the subreddit.

**DoNewNeedsApproval** (bitwise flag, int)  
- Whether given entity should await approval. [1 -> members, 2 -> posts, 4 -> comments]

**AllowedPostTypes** (bitwise flag, int)  
- What type of posts are allowed. [1 -> Text, 2 -> Image, 4 -> Link, 8 -> Repost]

**CreatorId** (User, read-only)
- The creator of the subreddit.

**CreatedAt** (datetime, read-only)  
- The date and time when the subreddit was created.

**UpdatedAt** (datetime, read-only)  
- The date and time when the subreddit was last updated.

---

### Endpoints:

1. **Get Lists**
   - **GET:** `api/search/?type=subreddit`  
     Search subreddits by title and description.
   - **GET:** `api/subreddits/{where}`  
     The `where` parameter determines the order in which the subreddits are displayed.
     - `api/subreddits/popular` - Ordered by `popularity`, for now amount of members.
     - `api/subreddits/new` - Ordered by creation time (`createdAt`), newest first.

2. **Get Single Element**
   - **GET:** `api/{subredditName}/about`  
     Provides full information about a specific subreddit.
   - **GET:** `api/{subredditName}/settings` | **auth: creator**  
     Provides full settings for the subreddit.

3. **Patch**
   - **PATCH:** `api/{subredditName}/settings` | **auth: creator**  
     Updates the settings of the subreddit.

4. **Create**
   - **POST:** `api/subreddits/create` | **auth: user**  
     Creates a new subreddit.

---

## Article Entity

**id**  
- Article ID.

**stringId**  
- Article ID string identifier visible to users.

**title**  
- Article title.

**isApproved**  
- Boolean indicating if the article is public.

**isNsfw**  
- Whether the article is NSFW.

**isSpoiler**  
- Whether the article is a spoiler.

**isLocked**  
- Whether the article is locked.

**isArchived**  
- Whether the article is archived.

**contentType**  
- Type of content. Possible values: `Text`, `Image`, `Link`, `Repost`.

**content**  
- The content of the article (string).

**SubredditId**  
- Relational connection to the containing Subreddit.

**creatorId**  
- Relational connection to the author ID.

**createdAt**  
- The date and time when the article was created.

**updatedAt**  
- The date and time when the article was last updated.

**deletedAt**  
- The date and time when the article was deleted.

---

### Endpoints:

1. **Get Lists**
   - **GET:** `api/search/?type=post`  
     Search posts by title or content, with sorting.
   - **GET:** `api/home-feed/{sort}`  
     Global feed visible from user-joined subreddits.
        - `api/home-feed/new` - Sorted by creation time (`createdAt`).
        <!-- - `api/home-feed/top` - Sorted by overall karma.
        - `api/home-feed/controversial` - Sorted by negative karma. -->
   - **GET:** `api/{subredditName}/{sort}`  
     Feed from a specific subreddit.
        - `api/{subredditName}/new` - Sorted by creation time (`createdAt`).
        <!-- - `api/{subredditName}/top` - Sorted by overall karma.
        - `api/{subredditName}/controversial` - Sorted by negative karma. -->

2. **Get Single Element**
   - **GET:** `api/{subredditName}/{ArticleStringId}`  
     Fetches details of a specific article.

3. **Patch**
   - **PATCH:** `api/{subredditName}/{ArticleStringId}` | **auth: creator | Subreddit creator**  
     Updates the settings of the article. Subreddit creator can edit settings but not content.

4. **Create**
   - **POST:** `api/{subredditName}/submit` | **auth: user**  
     Creates a new article.

5. **Delete**
   - **DELETE:** `api/{subredditName}/{ArticleStringId}` | **auth: creator | Subreddit creator**  
     Soft deletes an article. If deleted by the creator, the content is removed; if by the subreddit creator, the content stays.

---

## Comment Entity

1. **id** (integer, read-only)  
   - The unique identifier of the comment.

2. **stringId**  
   - Comment ID string identifier.

3. **content** (string, required)  
   - The content of the comment. Must be between 3 and 4000 characters long.

4. **isApproved** (boolean, optional)  
   - Whether the comment is approved.

5. **isOP** (boolean, optional)  
   - Indicates if the comment was made by an article creator.

6. **isSubCreator** (boolean, optional)  
   - Indicates if the comment was made by a subreddit creator. //in future might imply mod

7. **parentCommentId** (self-reference, optional)  
   - The parent comment, if this comment is a reply.

8. **creatorId** (User, optional)  
   - The user who authored the comment.

9. **ArticleId** (Thread, required)  
   - The post that the comment is associated with.

10. **createdAt** (datetime, read-only)  
    - The date and time when the comment was created.

11. **updatedAt** (datetime, read-only)  
    - The date and time when the comment was last modified.

12. **deletedAt** (datetime, read-only)  
    - The date and time when the comment was deleted.

---

### Endpoints:

1. **Get Lists**
   - **GET:** `api/search/?type=comment`  
     Search comments by content.
   - **GET:** `{subredditName}/{ArticleStringId}/comments`  
     Retrieves a collection of comments with pagination (25 items per page).
     - Example: `r/MadeMeSmile/comments/u33nuc/he_finally_got_his_acorn/`

2. **Get Single Element**
   - **GET:** `api/{subredditName}/{ArticleStringId}/comment/{commentStringId}`  
     Retrieves a specific comment.

3. **Patch**
   - **PATCH:** `api/comment/{commentStringId}` |  **auth: creator | Subreddit creator**  
     Updates the content. Subreddit creator can edit settings but not content.

4. **Create**
   - **POST:** `api/comment/` | **auth: user**  
     Creates a new comment.

5. **Delete**
   - **DELETE:** `api/comment/{commentStringId}` | **auth: creator | Subreddit creator**  
     Deletes a specific comment by its ID. If the comment has sub-comments, it will be soft deleted.

---

## Membership Entity

1. **id** (integer, read-only)  
   - The unique identifier of the User-Subreddit relationship.

2. **Member** (User, required)  
   - The user who has joined the subreddit. Represents the many-to-one relationship with the `User` entity.

3. **Subreddit** (Subreddit, required)  
   - The subreddit that the user has joined. Represents the many-to-one relationship with the `Subreddit` entity.

4. **isApproved** (boolean, required)  
   - Indicates whether the user's membership is approved.

<!-- 4. **isMod** (boolean, required)  
   - Indicates whether the user is a moderator. -->

5. **createdAt** (datetime, read-only)  
   - The date and time when the membership was created.

6. **updatedAt** (datetime, read-only)  
   - The date and time when the membership was last updated.

---

### Endpoints:

1. **Get Lists**
   - **GET:** `/membership/{where}`  | **auth: user**
     - `/membership/member` - Subreddits the user is a member of. 
     - `/membership/contributor` - Subreddits the user is an approved contributor in. 
     <!-- - `/membership/moderator` - Subreddits the user is an moderator in.  -->

2. **Patch**
   - **PATCH:** `api/membership` | **auth: SubredditCreator**  
     Updates the role or membership status.

3. **Create**
   - **POST:** `{subredditName}/join` | **auth: user**  
     Creates a new User-Subreddit relationship when a user joins a subreddit. Requires user authentication.

4. **Delete**
   - **POST:** `{subredditName}/leave` | **auth: user**  
     Removes a user from a subreddit (unsubscribe). Requires user authentication.

---

## User Entity

**id** (integer, read-only)  
- The unique identifier of the user.

**nickname** (string, optional)  
- The display name of the user.

**email** (string, optional)  
- The email address of the user. Must be a valid email format.

**description** (string, optional)  
- A brief description of the user.

**password** (string, required)  
- The hashed password of the user.

**isNsfw** (boolean, optional)  
- Indicates whether the user is marked as NSFW (Not Safe for Work).

**createdAt** (datetime, read-only)  
- The date and time when the user was created.

**updatedAt** (datetime, read-only)  
- The date and time when the user was last updated.

**deletedAt** (datetime, read-only)  
- The date and time when the user was deleted.

---

### Endpoints:

1. **Get Lists**
   - **GET:** `api/search/?type=user`  
     Search users by nickname.
   - **GET:** `api/user/{userNickname}/submitted/`  
     Get a list of articles made by the user. Parameters: `sort: Top, New`; `time` (time frame for top).

   - **GET:** `api/user/{userNickname}/comments/`  
     Get a list of comments made by the user. Parameters: `sort: Top, New`; `time` (time frame for top).

2. **Get Single User**
   - **GET:** `api/api/user/settings` | **auth: thisUser**  
     Provides full user settings.
   - **GET:** `api/user/{userNickname}`  
     Retrieves detailed information about a specific user.

3. **Patch**
   - **PATCH:** `/api/user/settings`  
     Update user settings.

4. **Delete**
   - **DELETE:** `/api/user/delete-account`  
     Parameters: 
     - `reason` (string, optional) - Reason for leaving.
     - `login`
     - `password`
     - `acceptedIrreversible` (boolean)  
     Soft deletes the user account, all tokens are revoked, content is disassociated.