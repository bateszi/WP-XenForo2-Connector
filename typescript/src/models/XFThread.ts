class XFThread {

	threadId: number;

	replyCount: number;

	url: string;

	constructor(threadId: number, replyCount: number, url: string) {
		this.threadId = threadId;
		this.replyCount = replyCount;
		this.url = url;
	}

}
