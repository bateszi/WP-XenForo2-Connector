///<reference path="models/XFThread.ts"/>

class XF2Connector {

	private replyCountElms: NodeListOf<Element>;

	private threadIds: Array<number> = [];

	private xfThreadIdToElmMap: XFThreadIdToElmMap = {};

	private xfBaseUrl = '';

	private xfThreads: XFThreadIdToThreadMap = {};

	constructor( xfBaseUrl: string ) {
		this.xfBaseUrl = xfBaseUrl;
	}

	private setActiveThreads(): void {
		this.replyCountElms = document.querySelectorAll('aukn-thread-replies');

		if (this.replyCountElms.length > 0) {
			let i = 0;

			while ( this.replyCountElms[i] ) {
				let replyCountElm = this.replyCountElms[i];
				let threadId = parseInt( replyCountElm.getAttribute('data-thread-id') );
				this.threadIds.push( threadId );
				this.xfThreadIdToElmMap[ threadId ] = replyCountElm;
				i++;
			}
		}
	}

	private fetchThreads(): void {
		if (this.threadIds.length > 0) {
			const threads = this.threadIds.join(',');
			const url = this.xfBaseUrl + '/index.php?api/threads/' + threads;

			let xhr = new XMLHttpRequest();

			xhr.onreadystatechange = () => {
				if (xhr.readyState === XMLHttpRequest.DONE) {
					if (xhr.status === 200) {
						let response = JSON.parse( xhr.responseText );

						Object.keys(response.threads).forEach(( threadId ) => {
							this.xfThreads[ parseInt(threadId) ] = new XFThread(
								parseInt(threadId),
								response.threads[threadId].replyCount,
								response.threads[threadId].url
							);
						});

						this.displayThreads();
					}
				}
			};

			xhr.open( 'GET', url, true );
			xhr.send();
		}
	}

	private displayThreads( beforeCommentsText: string = '&bull; ' ): void {
		Object.keys(this.xfThreads).forEach(( threadId ) => {
			let model = this.xfThreads[ parseInt(threadId) ],
				threadElm = this.xfThreadIdToElmMap[ parseInt(threadId) ],
				linkElm = document.createElement('a'),
				spanElm = document.createElement('span');

			linkElm.href = model.url;
			linkElm.target = '_blank';
			linkElm.text = ( model.replyCount === 1 )
				? model.replyCount + ' comment'
				: model.replyCount + ' comments';

			spanElm.innerHTML = beforeCommentsText;

			spanElm.appendChild( linkElm );
			threadElm.appendChild( spanElm );

			threadElm.className = 'loaded';
		});
	}

	public init(): void {
		this.setActiveThreads();
		this.fetchThreads();
	}

	public static load( xfBaseUrl: string ): void {
		let xf2 = new XF2Connector( xfBaseUrl );
		xf2.init();
	}

}


